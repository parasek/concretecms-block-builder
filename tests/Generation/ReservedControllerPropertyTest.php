<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\BlockGenerator\Exception\GenerationContributionConflictException;
use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\ControllerPhpFileGenerator;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\Text\TextFieldType;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;

/**
 * Test type: Generated controller property validation unit test.
 *
 * Verifies that a user-defined field cannot overwrite a controller property reserved by Concrete
 * CMS, preventing generated code from silently changing the controller's runtime behavior.
 */
final class ReservedControllerPropertyTest extends BlockBuilderTestCase
{
    /**
     * Verifies that a generated field cannot reuse Concrete's reserved cacheOutputOnEditMode property.
     */
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
