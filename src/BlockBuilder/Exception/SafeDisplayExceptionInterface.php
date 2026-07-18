<?php

declare(strict_types=1);

namespace BlockBuilder\Exception;

/**
 * Marks exceptions with a deliberately composed message safe to expose to dashboard or API users.
 * Presentation layers must still escape the message for their output format.
 */
interface SafeDisplayExceptionInterface
{
    public function getSafeDisplayMessage(): string;
}
