<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\FieldType;

use BlockBuilder\BlockGenerator\Generation\FieldGenerationContributorCollection;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContributorRegistry;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeRegistry;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;

/**
 * Test type: Field-type registry contract test.
 *
 * Verifies that every declared field type has one unambiguous implementation, generation
 * contributor, and canonical set of metadata.
 */
final class FieldTypeCatalogTest extends BlockBuilderTestCase
{
    /**
     * Confirms that every field-type enum has exactly one registered implementation and generation contributor.
     */
    public function testEveryEnumHasOneFieldTypeAndOneGenerationContributor(): void
    {
        $fieldTypeRegistry = $this->getService(FieldTypeRegistry::class);
        $contributorRegistry = $this->getService(FieldGenerationContributorRegistry::class);

        $enumHandles = array_map(
            static fn(FieldTypeEnum $fieldType): string => $fieldType->value,
            FieldTypeEnum::cases(),
        );
        $registeredFieldTypeHandles = array_map(
            static fn(object $fieldType): string => $fieldType::getFieldType()->value,
            $fieldTypeRegistry->all(),
        );
        $contributorHandles = array_map(
            static fn(object $contributor): string => $contributor->getFieldType()->value,
            $contributorRegistry->all(),
        );

        sort($enumHandles);
        sort($registeredFieldTypeHandles);
        sort($contributorHandles);

        self::assertSame($enumHandles, $registeredFieldTypeHandles);
        self::assertSame($enumHandles, $contributorHandles);
        self::assertCount(count(FieldTypeEnum::cases()), iterator_to_array(
            $this->getService(FieldGenerationContributorCollection::class),
        ));
    }

    /**
     * Confirms that every registered field type exposes valid, unique, and non-duplicated canonical metadata.
     */
    public function testFieldTypeMetadataIsCanonicalAndUnambiguous(): void
    {
        $handles = [];
        foreach ($this->getService(FieldTypeRegistry::class)->all() as $fieldType) {
            $fieldTypeEnum = $fieldType::getFieldType();
            $handles[] = $fieldTypeEnum->value;

            self::assertSame($fieldTypeEnum, FieldTypeEnum::from($fieldTypeEnum->value));
            self::assertNotSame('', trim($fieldType::getIcon()));
            self::assertContains('fieldType', $fieldType::getProperties());
            self::assertContains('handle', $fieldType::getProperties());
            self::assertContains('label', $fieldType::getProperties());
            self::assertSame(
                array_values(array_unique($fieldType::getProperties())),
                $fieldType::getProperties(),
            );
            $fieldType::getLegacyPropertyAliases();
        }

        self::assertSame(array_values(array_unique($handles)), $handles);
    }
}
