<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Security;

use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\Block\Validation\Validator\Block\BlockIconValidator;
use BlockBuilder\Block\Validation\Validator\Block\ExcludedFromRemovalValidator;
use BlockBuilder\Block\Validation\Validator\Block\LabelsValidator;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;

final class BlockInputSecurityValidationTest extends BlockBuilderTestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'block-builder-security-'
            . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->temporaryDirectory, 0700));
    }

    protected function tearDown(): void
    {
        foreach (scandir($this->temporaryDirectory) ?: [] as $fileName) {
            if ($fileName !== '.' && $fileName !== '..') {
                unlink($this->temporaryDirectory . DIRECTORY_SEPARATOR . $fileName);
            }
        }
        rmdir($this->temporaryDirectory);

        parent::tearDown();
    }

    public function testLabelErrorsOpenTheLabelsTab(): void
    {
        $feedback = $this->getService(LabelsValidator::class)->validate([
            'addAtTheTopLabel' => '',
            'addAtTheBottomLabel' => '',
            'basic' => [['fieldType' => 'text_field']],
            'basicLabel' => '',
            'entries' => [['fieldType' => 'text_field']],
            'entriesLabel' => '',
        ]);

        self::assertCount(3, $feedback->errors);
        self::assertSame([
            'addAtTheTopLabel',
            'basicLabel',
            'entriesLabel',
        ], $feedback->fieldsWithError);
        self::assertSame(['labels'], $feedback->tabsWithError);
    }

    public function testOneAddButtonLabelSatisfiesTheSharedLabelRequirement(): void
    {
        $feedback = $this->getService(LabelsValidator::class)->validate([
            'addAtTheTopLabel' => '',
            'addAtTheBottomLabel' => 'Add below',
            'basic' => [],
            'entries' => [],
        ]);

        self::assertSame([], $feedback->errors);
        self::assertSame([], $feedback->fieldsWithError);
        self::assertSame([], $feedback->tabsWithError);
    }

    /**
     * @dataProvider unsafeExclusionProvider
     */
    public function testUnsafeOrProtectedRemovalExclusionsAreRejected(
        string $excludedFromRemoval,
        string $expectedMessage,
    ): void {
        $feedback = $this->getService(ExcludedFromRemovalValidator::class)->validate([
            'excludedFromRemoval' => $excludedFromRemoval,
        ]);

        self::assertCount(1, $feedback->errors);
        self::assertStringContainsString($expectedMessage, $feedback->errors[0]);
        self::assertSame(['excludedFromRemoval'], $feedback->fieldsWithError);
        self::assertSame(['custom-code'], $feedback->tabsWithError);
    }

    public static function unsafeExclusionProvider(): array
    {
        return [
            'current directory' => ['.', 'must be a file or folder name'],
            'parent directory' => ['..', 'must be a file or folder name'],
            'forward traversal' => ['../private', 'must be a file or folder name'],
            'forward path' => ['templates/custom.php', 'must be a file or folder name'],
            'backslash traversal' => ['..\\private', 'must be a file or folder name'],
            'control character' => ["private\0file", 'must be a file or folder name'],
            'generated config' => ['config-bb.json', 'You cannot exclude "config-bb.json"'],
            'generated controller' => ['controller.php', 'You cannot exclude "controller.php"'],
            'generated database schema' => ['db.xml', 'You cannot exclude "db.xml"'],
            'generated block icon' => ['icon.png', 'You cannot exclude "icon.png"'],
        ];
    }

    public function testPlainBasenamesCanBeExcludedFromRemoval(): void
    {
        $feedback = $this->getService(ExcludedFromRemovalValidator::class)->validate([
            'excludedFromRemoval' => "templates\ncustom.php\nassets",
        ]);

        self::assertSame([], $feedback->errors);
        self::assertSame([], $feedback->fieldsWithError);
        self::assertSame([], $feedback->tabsWithError);
    }

    public function testUploadErrorIsReportedBeforeReadingTheIcon(): void
    {
        $path = $this->writeFile('partial-upload.png', 'partial');
        $uploadedFile = new UploadedFile(
            $path,
            'icon.png',
            'image/png',
            UPLOAD_ERR_PARTIAL,
            true,
        );

        $feedback = $this->validateUploadedIcon($uploadedFile);

        self::assertSame(
            ['The uploaded "Custom block icon" file is invalid (Block settings).'],
            $feedback->errors,
        );
        $this->assertCustomIconFeedbackLocation($feedback);
    }

    public function testOversizedIconIsRejectedBeforeImageParsing(): void
    {
        $path = $this->writeFile('large.png', str_repeat('x', 1_048_577));

        $feedback = $this->validateUploadedIcon(new UploadedFile(
            $path,
            'icon.png',
            'image/png',
            UPLOAD_ERR_OK,
            true,
        ));

        self::assertSame(
            ['The "Custom block icon" must not be larger than 1 MB (Block settings).'],
            $feedback->errors,
        );
        $this->assertCustomIconFeedbackLocation($feedback);
    }

    public function testNonImageIconIsRejected(): void
    {
        $path = $this->writeFile('not-an-image.png', 'not an image');

        $feedback = $this->validateUploadedIcon(new UploadedFile(
            $path,
            'icon.png',
            'image/png',
            UPLOAD_ERR_OK,
            true,
        ));

        self::assertSame(
            ['The uploaded "Custom block icon" is not a valid image (Block settings).'],
            $feedback->errors,
        );
        $this->assertCustomIconFeedbackLocation($feedback);
    }

    public function testImageContentControlsMimeAndDimensionsRatherThanClientMetadata(): void
    {
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==', true);
        self::assertNotFalse($gif);
        $path = $this->writeFile('disguised.png', $gif);

        $feedback = $this->validateUploadedIcon(new UploadedFile(
            $path,
            'icon.png',
            'image/png',
            UPLOAD_ERR_OK,
            true,
        ));

        self::assertSame([
            'The "Custom block icon" must be a PNG image (Block settings).',
            'The "Custom block icon" must be exactly 97px x 97px. Current size: 1px x 1px (Block settings).',
        ], $feedback->errors);
        $this->assertCustomIconFeedbackLocation($feedback);
    }

    public function testPngWithWrongDimensionsIsRejected(): void
    {
        $path = $this->writeFile('small.png', $this->createPng(1, 1));

        $feedback = $this->validateUploadedIcon(new UploadedFile(
            $path,
            'icon.png',
            'application/octet-stream',
            UPLOAD_ERR_OK,
            true,
        ));

        self::assertSame([
            'The "Custom block icon" must be exactly 97px x 97px. Current size: 1px x 1px (Block settings).',
        ], $feedback->errors);
        $this->assertCustomIconFeedbackLocation($feedback);
    }

    public function testValidPngIconIsAccepted(): void
    {
        $path = $this->writeFile('valid.png', $this->createPng(97, 97));

        $feedback = $this->validateUploadedIcon(new UploadedFile(
            $path,
            'icon-with-untrusted-name.php',
            'application/octet-stream',
            UPLOAD_ERR_OK,
            true,
        ));

        self::assertSame([], $feedback->errors);
        self::assertSame([], $feedback->fieldsWithError);
        self::assertSame([], $feedback->tabsWithError);
    }

    private function validateUploadedIcon(UploadedFile $uploadedFile): ValidationFeedback
    {
        return $this->getService(BlockIconValidator::class)->validate(
            [],
            new FileBag(['customBlockIcon' => $uploadedFile]),
        );
    }

    private function assertCustomIconFeedbackLocation(ValidationFeedback $feedback): void
    {
        self::assertSame(['customBlockIcon'], $feedback->fieldsWithError);
        self::assertSame(['block-settings'], $feedback->tabsWithError);
    }

    private function writeFile(string $fileName, string $contents): string
    {
        $path = $this->temporaryDirectory . DIRECTORY_SEPARATOR . $fileName;
        self::assertSame(strlen($contents), file_put_contents($path, $contents));

        return $path;
    }

    private function createPng(int $width, int $height): string
    {
        $header = pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0);
        $row = "\0" . str_repeat("\0", $width * 4);
        $pixels = str_repeat($row, $height);

        return "\x89PNG\r\n\x1a\n"
            . $this->createPngChunk('IHDR', $header)
            . $this->createPngChunk('IDAT', gzcompress($pixels))
            . $this->createPngChunk('IEND', '');
    }

    private function createPngChunk(string $type, string $data): string
    {
        $chunk = $type . $data;
        $checksum = hexdec(hash('crc32b', $chunk));

        return pack('N', strlen($data)) . $chunk . pack('N', $checksum);
    }
}
