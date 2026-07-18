<?php

declare(strict_types=1);

namespace BlockBuilder\Exception;

use RuntimeException;
use Throwable;

abstract class SafeDisplayException extends RuntimeException implements SafeDisplayExceptionInterface
{
    public function __construct(
        string $message,
        private readonly string $safeDisplayMessage,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    final public function getSafeDisplayMessage(): string
    {
        return $this->safeDisplayMessage;
    }
}
