<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\BlockGenerator\Exception\GeneratedFileDefinitionException;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFile;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFileCollection;
use PHPUnit\Framework\TestCase;

final class GeneratedTextFileCollectionTest extends TestCase
{
    /**
     * @dataProvider invalidDestinationProvider
     */
    public function testUnsafeDestinationIsRejected(string $relativePath): void
    {
        $this->expectException(GeneratedFileDefinitionException::class);

        new GeneratedTextFileCollection([
            new GeneratedTextFile($relativePath, 'contents', 'unsafe generator'),
        ]);
    }

    public function invalidDestinationProvider(): array
    {
        return [
            'empty' => [''],
            'unix absolute' => ['/controller.php'],
            'windows absolute' => ['C:/controller.php'],
            'backslash' => ['nested\\controller.php'],
            'control character' => ["nested/cont\0roller.php"],
            'empty segment' => ['nested//controller.php'],
            'current directory segment' => ['nested/./controller.php'],
            'parent directory segment' => ['nested/../controller.php'],
            'leading empty segment' => ['/nested/controller.php'],
            'trailing empty segment' => ['nested/'],
        ];
    }

    public function testBlankProducerIsRejected(): void
    {
        $this->expectException(GeneratedFileDefinitionException::class);
        $this->expectExceptionMessage('identify its producer');

        new GeneratedTextFileCollection([
            new GeneratedTextFile('controller.php', 'contents', " \t\n"),
        ]);
    }

    public function testCaseInsensitiveDestinationCollisionReportsBothProducers(): void
    {
        $collection = new GeneratedTextFileCollection([
            new GeneratedTextFile('Nested/Controller.php', 'first', 'first generator'),
        ]);

        try {
            $collection->add(new GeneratedTextFile('nested/controller.php', 'second', 'second generator'));
            self::fail('Case-only destination collisions must be rejected on every filesystem.');
        } catch (GeneratedFileDefinitionException $exception) {
            self::assertStringContainsString('first generator', $exception->getMessage());
            self::assertStringContainsString('second generator', $exception->getMessage());
            self::assertStringContainsString('nested/controller.php', $exception->getMessage());
        }

        self::assertCount(1, $collection);
    }

    public function testIterationIsStableAndSortedByRelativePath(): void
    {
        $collection = new GeneratedTextFileCollection([
            new GeneratedTextFile('view.php', 'view', 'view generator'),
            new GeneratedTextFile('nested/form.php', 'form', 'form generator'),
            new GeneratedTextFile('controller.php', 'controller', 'controller generator'),
        ]);

        $relativePaths = [];
        foreach ($collection as $generatedFile) {
            $relativePaths[] = $generatedFile->relativePath;
        }

        self::assertSame(['controller.php', 'nested/form.php', 'view.php'], $relativePaths);
        self::assertCount(3, $collection);
    }
}
