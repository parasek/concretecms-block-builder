<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Security;

use BlockBuilder\Block\Validation\Validator\Block\BlockHandleValidator;
use BlockBuilder\Block\Validation\Validator\Block\BlockIconValidator;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use Symfony\Component\HttpFoundation\FileBag;

final class RequestOwnershipValidationTest extends BlockBuilderTestCase
{
    public function testRebuildHandleMustMatchTheLoadedConfigRoute(): void
    {
        $feedback = $this->getService(BlockHandleValidator::class)->validate([
            'blockHandle' => 'target_block',
            'rebuildBlock' => true,
            'rebuildSourceHandle' => 'source_block',
        ]);

        self::assertNotEmpty($feedback->errors);
        self::assertStringContainsString('own loaded configuration', $feedback->errors[0]);
        self::assertContains('blockHandle', $feedback->fieldsWithError);
    }

    public function testMalformedCustomIconIsReportedAsValidationFeedback(): void
    {
        $files = new FileBag([
            'customBlockIcon' => ['unexpected' => 'value'],
        ]);

        $feedback = $this->getService(BlockIconValidator::class)->validate([], $files);

        self::assertNotEmpty($feedback->errors);
        self::assertContains('customBlockIcon', $feedback->fieldsWithError);
    }
}
