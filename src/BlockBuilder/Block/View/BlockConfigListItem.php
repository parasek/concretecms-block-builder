<?php

declare(strict_types=1);

namespace BlockBuilder\Block\View;

use BlockBuilder\Block\Dto\BlockConfigDto;

readonly class BlockConfigListItem
{
    public function __construct(
        public BlockConfigDto $config,
        public bool $installed,
        public string $iconPath,
        public string $loadUrl,
        public ?int $blockTypeId = null,
        public ?int $usageCount = null,
        public ?int $activeUsageCount = null,
        public ?string $usageUrl = null,
        public ?string $installUrl = null,
        public ?string $uninstallUrl = null,
        public ?string $deleteDirectoryUrl = null,
    ) {
    }
}
