<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class BlockGenerationPlan
{
    public function __construct(
        public ControllerGenerationPlan $controller,
        public DatabaseGenerationPlan $database,
        public FormGenerationPlan $form,
        public ViewGenerationPlan $view,
        public FrontendAssetGenerationPlan $javaScript,
        public FrontendAssetGenerationPlan $css,
    ) {
    }
}
