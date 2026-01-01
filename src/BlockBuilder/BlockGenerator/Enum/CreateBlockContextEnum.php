<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Enum;

/**
 * Represents controller action used to create a block.
 *
 * @see \Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks\BlockBuilder
 */
enum CreateBlockContextEnum: string
{
    case NewBlock = 'view';
    case Config = 'config';
    case PredefinedConfig = 'predefined_config';
}
