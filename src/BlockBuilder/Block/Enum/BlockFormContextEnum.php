<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Enum;

/**
 * Identifies how the block creation form was opened.
 */
enum BlockFormContextEnum: string
{
    case NewBlock = 'view';
    case Config = 'config';
    case PredefinedConfig = 'predefined_config';
}
