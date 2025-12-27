<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Service;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Exception\ConfigLoadingException;
use BlockBuilder\Block\Service\BlockConfigReader;
use BlockBuilder\Block\Service\BlockOwnershipChecker;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use RuntimeException;
use Throwable;

/**
 * Test type: Block ownership service unit test.
 *
 * Verifies that matching generated configurations establish ownership, expected loading failures
 * mean unowned blocks, and unexpected reader failures remain visible to callers.
 */
final class BlockOwnershipCheckerTest extends BlockBuilderTestCase
{
    /**
     * Confirms that a readable generated configuration whose handle matches identifies the
     * application block as owned by Block Builder.
     */
    public function testReadableMatchingConfigurationIdentifiesAnOwnedBlock(): void
    {
        $state = new BlockOwnershipCheckerTestState();
        $state->config = $this->createAllFieldTypesConfig();
        $checker = new BlockOwnershipChecker(new BlockOwnershipCheckerTestReader($state));

        self::assertTrue($checker->isOwnedApplicationBlock('all_field_types_test'));
        self::assertSame(['all_field_types_test'], $state->requestedHandles);
    }

    /**
     * Confirms that the expected configuration-loading failure safely identifies a block as not
     * owned by Block Builder.
     */
    public function testExpectedConfigurationLoadingFailureIdentifiesAnUnownedBlock(): void
    {
        $state = new BlockOwnershipCheckerTestState();
        $state->failure = new ConfigLoadingException('configuration is unavailable');
        $checker = new BlockOwnershipChecker(new BlockOwnershipCheckerTestReader($state));

        self::assertFalse($checker->isOwnedApplicationBlock('example_block'));
        self::assertSame(['example_block'], $state->requestedHandles);
    }

    /**
     * Confirms that an unexpected reader failure is rethrown rather than being hidden as a simple
     * unowned result.
     */
    public function testUnexpectedReaderFailureIsNotMisreportedAsAnUnownedBlock(): void
    {
        $failure = new RuntimeException('unexpected reader infrastructure failure');
        $state = new BlockOwnershipCheckerTestState();
        $state->failure = $failure;
        $checker = new BlockOwnershipChecker(new BlockOwnershipCheckerTestReader($state));

        try {
            $checker->isOwnedApplicationBlock('example_block');
            self::fail('Unexpected reader failures must remain visible to the caller.');
        } catch (RuntimeException $exception) {
            self::assertSame($failure, $exception);
        }

        self::assertSame(['example_block'], $state->requestedHandles);
    }
}

final class BlockOwnershipCheckerTestState
{
    /** @var string[] */
    public array $requestedHandles = [];
    public ?BlockConfigDto $config = null;
    public ?Throwable $failure = null;
}

final readonly class BlockOwnershipCheckerTestReader extends BlockConfigReader
{
    public function __construct(private BlockOwnershipCheckerTestState $state)
    {
    }

    public function getConfigFromApplicationFolder(string $blockHandle): BlockConfigDto
    {
        $this->state->requestedHandles[] = $blockHandle;
        if ($this->state->failure !== null) {
            throw $this->state->failure;
        }

        return $this->state->config
            ?? throw new RuntimeException('No configuration result was prepared for the ownership test.');
    }
}
