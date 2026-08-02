<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\BlockGenerator\Exception\GenerationContributionConflictException;
use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\ControllerPhpFileGenerator;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\Text\TextFieldType;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;

final class ReservedControllerPropertyTest extends BlockBuilderTestCase
{
    public function testFieldCannotShadowCacheOutputOnEditModeProperty(): void
    {
        $config = $this->createBlockConfigDtoFactory()->fromGenerationArray([
            'blockName' => 'Reserved Property Test',
            'blockHandle' => 'reserved_property_test',
            'basic' => [[
                'fieldType' => FieldTypeEnum::Text->value,
                'label' => 'Conflicting field',
                'handle' => 'btCacheBlockOutputOnEditMode',
                'required' => false,
                'helpText' => '',
                ...TextFieldType::getDefaultValues(),
            ]],
            'entries' => [],
        ]);
        $context = $this->createGenerationContext($config);

        $this->expectException(GenerationContributionConflictException::class);
        $this->expectExceptionMessage('btCacheBlockOutputOnEditMode');

        $this->getService(ControllerPhpFileGenerator::class)->generate($context);
    }
}
