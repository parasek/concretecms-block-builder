<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp;

use BlockBuilder\Block\Dto\CreateBlockDto;
use BlockBuilder\Block\Dto\CreateBlockManifestDto;
use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Factory\ControllerPhpFieldTypeStrategyFactory;
use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Service\ControllerPhpFormatterService;
use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Visitor\ControllerPhpVisitor;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorInterface;
use BlockBuilder\Environment\EnvironmentService;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\BlockGenerator\FileGenerator\AbstractFileGenerator;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;
use BlockBuilder\FieldType\FieldTypeInterface;

readonly class ControllerPhpFileGenerator extends AbstractFileGenerator implements FileGeneratorInterface
{
    const string CONTROLLER_PHP_STUB_FILE = 'controller.php.stub';

    public function __construct(
        private ControllerPhpFieldTypeStrategyFactory $strategyFactory,
        private ControllerPhpFormatterService $controllerPhpFormatter,
        EnvironmentService $environmentService,
    ) {
        parent::__construct($environmentService);
    }

    public function getOutput(CreateBlockDto $dto, CreateBlockManifestDto $manifestDto): string
    {
        $visitor = new ControllerPhpVisitor();

        $this->processFields($dto->basic, $visitor, FieldTypeContextEnum::BasicFields);
        $this->processFields($dto->entries, $visitor, FieldTypeContextEnum::RepeatableFields);

        return $this->generateOutput(
            stubFileName: self::CONTROLLER_PHP_STUB_FILE,
            specificReplacements: [
                '{{USE_STATEMENTS}}' => $this->controllerPhpFormatter->formatUseStatements($visitor->getUseStatements()),
                '{{SEARCHABLE_FIELDS}}' => $this->controllerPhpFormatter->formatSearchableFields($visitor->getSearchableBasicFields(), $visitor->getSearchableEntryFields()),
            ],
            dto: $dto,
            manifestDto: $manifestDto
        );
    }

    private function processFields(array $fields, ControllerPhpVisitor $visitor, FieldTypeContextEnum $context): void
    {
        foreach ($fields as $fieldTypeDto) {
            /** @var FieldTypeDtoInterface $fieldTypeDto */
            /** @var FieldTypeEnum $enum */
            $enum = $fieldTypeDto->fieldType;
            $fieldType = $enum->getInstance();
            $strategy = $this->strategyFactory->create(fieldType: $fieldType);
            $visitor->visit($strategy, $fieldTypeDto, $context);
        }
    }
}
