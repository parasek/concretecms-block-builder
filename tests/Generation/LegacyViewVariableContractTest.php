<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;
use BlockBuilder\FieldType\Type\Image\ImageFieldTypeDto;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;

/**
 * Test type: Legacy generated-view compatibility contract test.
 *
 * Verifies that current generation still exposes the public view variables and helper suffixes
 * promised by version 2.8.1 for all basic and repeatable field types.
 */
final class LegacyViewVariableContractTest extends BlockBuilderTestCase
{
    private const array COMMON_LINK_SUFFIXES = [
        '_link',
        '_ending',
        '_text',
        '_title',
        '_new_window',
        '_no_follow',
    ];

    private const array IMAGE_SUFFIXES = [
        '',
        '_object',
        '_filename',
        '_type',
        '_relativePath',
        '_link',
        '_alt',
        '_width',
        '_height',
        '_fullscreenLink',
        '_fullscreenWidth',
        '_fullscreenHeight',
        '_thumbnailLink',
        '_thumbnailWidth',
        '_thumbnailHeight',
    ];

    /**
     * Verifies that current generation preserves the public view variables promised by version 2.8.1.
     */
    public function testGenerationPlanPreservesVersion281PublicViewContract(): void
    {
        $config = $this->createBlockConfigDtoFactory()->fromArray(
            $this->loadJsonFixture('all-fields-2.8.1.json'),
        );
        $plan = $this->createGenerationContext($config)->plan;
        $actualVariables = array_map(static fn(object $variable): string => $variable->name, $plan->view->variables);
        $actualEntryKeys = array_map(static fn(object $variable): string => $variable->name, $plan->view->entryKeys);

        $expectedVariables = [];
        $expectedEntryKeys = [];
        foreach ($config->basic as $field) {
            $contract = $this->getLegacyContract($field, false);
            array_push($expectedVariables, ...$contract['variables']);
        }
        foreach ($config->entries as $field) {
            $contract = $this->getLegacyContract($field, true);
            array_push($expectedVariables, ...$contract['variables']);
            array_push($expectedEntryKeys, ...$contract['entryKeys']);
        }

        foreach (array_values(array_unique($expectedVariables)) as $variableName) {
            self::assertContains(
                $variableName,
                $actualVariables,
                sprintf('Legacy view variable "$%s" is missing from the generation plan.', $variableName),
            );
        }
        foreach (array_values(array_unique($expectedEntryKeys)) as $entryKey) {
            self::assertContains(
                $entryKey,
                $actualEntryKeys,
                sprintf('Legacy repeatable key "%s" is missing from the generation plan.', $entryKey),
            );
        }
    }

    /**
     * @return array{variables: string[], entryKeys: string[]}
     */
    private function getLegacyContract(FieldTypeDtoInterface $field, bool $repeatable): array
    {
        $fieldType = $field->fieldType;
        $handle = $field->handle;
        $topLevelVariables = [];
        $fieldNames = [];

        if ($fieldType === FieldTypeEnum::FlexLink) {
            $suffixes = [
                '',
                ...self::COMMON_LINK_SUFFIXES,
                '_name',
                '_filename',
                '_link_type',
                '_protocol',
            ];
            if (!$repeatable) {
                $suffixes[] = '_object';
            }
            $fieldNames = $this->appendSuffixes($handle, $suffixes);
        } elseif ($fieldType === FieldTypeEnum::LinkFromSitemap) {
            $suffixes = ['', ...self::COMMON_LINK_SUFFIXES, '_name', '_link_type'];
            if (!$repeatable) {
                $suffixes[] = '_object';
            }
            $fieldNames = $this->appendSuffixes($handle, $suffixes);
        } elseif ($fieldType === FieldTypeEnum::LinkFromFileManager) {
            $suffixes = ['', ...self::COMMON_LINK_SUFFIXES, '_filename', '_link_type'];
            if (!$repeatable) {
                $suffixes[] = '_object';
            }
            $fieldNames = $this->appendSuffixes($handle, $suffixes);
        } elseif ($fieldType === FieldTypeEnum::ExternalLink) {
            $fieldNames = $this->appendSuffixes(
                $handle,
                ['', ...self::COMMON_LINK_SUFFIXES, '_link_type', '_protocol'],
            );
        } elseif ($fieldType === FieldTypeEnum::Image) {
            self::assertInstanceOf(ImageFieldTypeDto::class, $field);
            $imageSuffixes = self::IMAGE_SUFFIXES;
            if ($repeatable) {
                $imageSuffixes = array_values(array_diff($imageSuffixes, ['_object']));
            }
            $fieldNames = $this->appendSuffixes($handle, $imageSuffixes);
            $topLevelVariables = $this->getLegacyImageDefaultVariables($field, $repeatable);
        } elseif ($fieldType === FieldTypeEnum::FileSet) {
            $fieldNames = [$handle, $handle . '_files'];
        } elseif (in_array($fieldType, [FieldTypeEnum::SingleChoice, FieldTypeEnum::MultipleChoice], true)) {
            $fieldNames = [$handle];
            $topLevelVariables[] = ($repeatable ? 'entry_' : '') . $handle . '_options';
        } else {
            $fieldNames = [$handle];
        }

        if (!$repeatable) {
            array_push($topLevelVariables, ...$fieldNames);
            $fieldNames = [];
        }

        return [
            'variables' => $topLevelVariables,
            'entryKeys' => $fieldNames,
        ];
    }

    /**
     * @param string[] $suffixes
     * @return string[]
     */
    private function appendSuffixes(string $handle, array $suffixes): array
    {
        return array_map(static fn(string $suffix): string => $handle . $suffix, $suffixes);
    }

    /**
     * @return string[]
     */
    private function getLegacyImageDefaultVariables(ImageFieldTypeDto $field, bool $repeatable): array
    {
        $suffixPrefix = $repeatable ? '_defaultRepeatable' : '_default';
        $variables = [];
        if ($field->createThumbnailImage && $field->thumbnailEditable) {
            if ($field->thumbnailWidth !== null) {
                $variables[] = $field->handle . $suffixPrefix . 'ThumbnailWidth';
            }
            if ($field->thumbnailHeight !== null) {
                $variables[] = $field->handle . $suffixPrefix . 'ThumbnailHeight';
            }
        }
        if ($field->createFullscreenImage && $field->fullscreenEditable) {
            if ($field->fullscreenWidth !== null) {
                $variables[] = $field->handle . $suffixPrefix . 'FullscreenWidth';
            }
            if ($field->fullscreenHeight !== null) {
                $variables[] = $field->handle . $suffixPrefix . 'FullscreenHeight';
            }
        }

        return $variables;
    }
}
