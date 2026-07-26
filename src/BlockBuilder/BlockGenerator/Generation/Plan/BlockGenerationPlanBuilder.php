<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class BlockGenerationPlanBuilder
{
    public ControllerGenerationPlanBuilder $controller;
    public DatabaseGenerationPlanBuilder $database;
    public FormGenerationPlanBuilder $form;
    public ViewGenerationPlanBuilder $view;
    public FrontendAssetGenerationPlanBuilder $javaScript;
    public FrontendAssetGenerationPlanBuilder $css;

    public function __construct()
    {
        $this->controller = new ControllerGenerationPlanBuilder();
        $this->database = new DatabaseGenerationPlanBuilder();
        $this->form = new FormGenerationPlanBuilder();
        $this->view = new ViewGenerationPlanBuilder();
        $this->javaScript = FrontendAssetGenerationPlanBuilder::createForJavaScript();
        $this->css = FrontendAssetGenerationPlanBuilder::createForStylesheet();
    }

    public function build(): BlockGenerationPlan
    {
        return new BlockGenerationPlan(
            controller: $this->controller->build(),
            database: $this->database->build(),
            form: $this->form->build(),
            view: $this->view->build(),
            javaScript: $this->javaScript->build(),
            css: $this->css->build(),
        );
    }
}
