<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

final readonly class BlockGenerationPlanBuilder
{
    public ControllerGenerationPlanBuilder $controller;
    public DatabaseGenerationPlanBuilder $database;
    public FormGenerationPlanBuilder $form;
    public ViewGenerationPlanBuilder $view;

    public function __construct()
    {
        $this->controller = new ControllerGenerationPlanBuilder();
        $this->database = new DatabaseGenerationPlanBuilder();
        $this->form = new FormGenerationPlanBuilder();
        $this->view = new ViewGenerationPlanBuilder();
    }

    public function build(): BlockGenerationPlan
    {
        return new BlockGenerationPlan(
            controller: $this->controller->build(),
            database: $this->database->build(),
            form: $this->form->build(),
            view: $this->view->build(),
        );
    }
}
