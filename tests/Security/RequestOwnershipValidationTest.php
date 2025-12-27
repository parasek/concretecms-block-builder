<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Security;

use BlockBuilder\Block\Validation\Validator\Block\BlockHandleValidator;
use BlockBuilder\Block\Validation\Validator\Block\BlockIconValidator;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * Test type: Request ownership security validation unit test.
 *
 * Verifies that rebuild requests belong to the loaded block configuration and that malformed
 * custom icons are reported as validation errors instead of escaping the request pipeline.
 */
final class RequestOwnershipValidationTest extends BlockBuilderTestCase
{
    /**
     * Confirms that a rebuild request cannot name a block handle different from the configuration
     * loaded by the current route.
     */
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

    /**
     * Confirms that an unexpected custom-icon upload shape becomes ordinary validation feedback
     * instead of causing an unhandled request error.
     */
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
