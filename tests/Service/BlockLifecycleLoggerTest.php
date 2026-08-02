<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Service;

use BlockBuilder\Block\Service\BlockLifecycleLogger;
use Concrete\Core\User\User;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class BlockLifecycleLoggerTest extends TestCase
{
    public function testSuccessIsLoggedAtNoticeLevelWithProtectedLifecycleContext(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $user = $this->createMock(User::class);
        $user->expects(self::once())
            ->method('getUserID')
            ->willReturn('37');
        $logger->expects(self::once())
            ->method('notice')
            ->with(
                'Block Builder lifecycle operation "{operation}" succeeded for target "{target}".',
                [
                    'operation' => 'install',
                    'target' => 'example_block',
                    'userId' => '37',
                    'path' => '/application/blocks/example_block',
                ],
            );

        $lifecycleLogger = new BlockLifecycleLogger($logger, $user);
        $lifecycleLogger->logSuccess(
            operation: 'install',
            target: 'example_block',
            context: [
                'operation' => 'untrusted_operation',
                'target' => 'untrusted_target',
                'userId' => '999',
                'path' => '/application/blocks/example_block',
            ],
        );
    }

    public function testFailureIsLoggedAtWarningLevelWithExactExceptionAndErrorMessage(): void
    {
        $failure = new RuntimeException('simulated lifecycle failure');
        $logger = $this->createMock(LoggerInterface::class);
        $user = $this->createMock(User::class);
        $user->expects(self::once())
            ->method('getUserID')
            ->willReturn('1');
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'Block Builder lifecycle operation "{operation}" failed for target "{target}".'
                    . PHP_EOL
                    . '{errorMessage}',
                [
                    'operation' => 'uninstall',
                    'target' => 42,
                    'userId' => '1',
                    'blockTypeId' => 42,
                    'errorMessage' => 'simulated lifecycle failure',
                    'exception' => $failure,
                ],
            );

        $lifecycleLogger = new BlockLifecycleLogger($logger, $user);
        $lifecycleLogger->logFailure(
            operation: 'uninstall',
            target: 42,
            exception: $failure,
            context: [
                'operation' => 'untrusted_operation',
                'target' => 'untrusted_target',
                'userId' => '999',
                'blockTypeId' => 42,
                'errorMessage' => 'untrusted message',
                'exception' => new RuntimeException('untrusted exception'),
            ],
        );
    }
}
