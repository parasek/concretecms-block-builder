<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Enum;

/**
 * Represents the state of a block after generation.
 */
enum PostGenerationBlockStateEnum: string
{
    case Rebuilt = 'rebuilt';
    case CreatedAndInstalled = 'created_and_installed';
    case Created = 'created';
}
