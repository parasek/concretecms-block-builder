<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Enum;

/**
 * Represents state of block after a generation process.
 */
enum PostGenerationBlockStateEnum: string
{
    case Rebuilt = 'rebuilt';
    case CreatedAndInstalled = 'created_and_installed';
    case Created = 'created';
}
