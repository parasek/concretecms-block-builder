<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use Concrete\Core\User\User;
use Psr\Log\LoggerInterface;

readonly class BlockLifecycleLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private User $user,
    ) {
    }

    public function logSuccess(string $operation, string|int $target, array $context = []): void
    {
        $this->logger->notice(
            'Block Builder lifecycle operation "{operation}" succeeded for target "{target}".',
            $this->createContext($operation, $target, $context),
        );
    }

    public function logFailure(
        string $operation,
        string|int $target,
        \Throwable $exception,
        array $context = [],
    ): void {
        $this->logger->warning(
            'Block Builder lifecycle operation "{operation}" failed for target "{target}".'
                . PHP_EOL
                . '{errorMessage}',
            array_merge(
                $this->createContext($operation, $target, $context),
                [
                    'errorMessage' => $exception->getMessage(),
                    'exception' => $exception,
                ],
            ),
        );
    }

    private function createContext(string $operation, string|int $target, array $context): array
    {
        return [
            'operation' => $operation,
            'target' => $target,
            'userId' => $this->user->getUserID(),
        ] + $context;
    }
}
