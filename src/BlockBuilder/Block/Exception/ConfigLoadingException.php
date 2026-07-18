<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Exception;

use BlockBuilder\Exception\SafeDisplayException;
use Throwable;

class ConfigLoadingException extends SafeDisplayException
{
    public function __construct(
        string $message,
        ?string $safeDisplayMessage = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            safeDisplayMessage: $safeDisplayMessage ?? t('The configuration file could not be loaded.'),
            previous: $previous,
        );
    }
}
