<?php

declare(strict_types=1);

namespace BlockBuilder\Service\Option;

use BlockBuilder\Block\Service\BlockIconProvider;
use BlockBuilder\Block\Enum\BlockFormContextEnum;
use BlockBuilder\Environment\EnvironmentService;

readonly class BlockIconOptionProvider
{
    public function __construct(
        private BlockIconProvider $blockIconProvider,
        private EnvironmentService $environmentService,
    ) {
    }

    public function getOptions(BlockFormContextEnum $context, string $blockHandle): array
    {
        $icons = [];
        if ($context === BlockFormContextEnum::Config) {
            $publicPath = $this->environmentService->getPublicPathToBlockIcon($blockHandle);
            if (file_exists(DIR_BASE . $publicPath)) {
                $icons[] = ['path' => $publicPath, 'label' => t('Keep current icon')];
            }
        }

        $icons[] = [
            'path' => $this->environmentService->getPublicPathToDefaultBlockIcon(),
            'label' => t('Default Block Builder icon'),
        ];

        $options = [];
        foreach (array_merge($icons, $this->blockIconProvider->getPublicPaths()) as $icon) {
            $options[$icon['path']] = $icon['label'];
        }

        return $options;
    }
}
