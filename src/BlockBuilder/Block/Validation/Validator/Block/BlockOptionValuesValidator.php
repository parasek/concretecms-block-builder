<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\ValidatorInterface;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\Block\Validation\ValidationFeedbackBuilder;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use BlockBuilder\Service\Option\BlockSettingsOptionProvider;
use Symfony\Component\HttpFoundation\FileBag;

class BlockOptionValuesValidator implements ValidatorInterface
{
    private const array BOOLEAN_OPTIONS = ['0', '1'];

    private const array BLOCK_SETTINGS_BOOLEAN_FIELDS = [
        'cacheBlockRecord',
        'cacheBlockOutput',
        'cacheBlockOutputOnPost',
        'cacheBlockOutputOnEditMode',
        'cacheBlockOutputForRegisteredUsers',
        'supportSavingNullValues',
        'ignorePageThemeGridFrameworkContainer',
    ];

    private const array BUILD_OPTIONS_BOOLEAN_FIELDS = [
        'installBlock',
        'entriesAsFirstTab',
        'highlightMultiElementFields',
    ];

    public function __construct(
        private readonly BlockSettingsOptionProvider $blockSettingsOptions,
    ) {
    }

    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $feedback = new ValidationFeedbackBuilder();

        foreach (self::BLOCK_SETTINGS_BOOLEAN_FIELDS as $field) {
            $this->validateOption($feedback, $data, $field, self::BOOLEAN_OPTIONS, NavigationTabEnum::BlockSettings);
        }

        foreach (self::BUILD_OPTIONS_BOOLEAN_FIELDS as $field) {
            $this->validateOption($feedback, $data, $field, self::BOOLEAN_OPTIONS, NavigationTabEnum::BuildOptions);
        }

        // Block type set
        $this->validateOption(
            $feedback,
            $data,
            'blockTypeSet',
            $this->getStringKeys($this->blockSettingsOptions->getBlockTypeSets(includeEmptyOption: true)),
            NavigationTabEnum::BlockSettings,
        );

        return $feedback->build();
    }

    private function validateOption(
        ValidationFeedbackBuilder $feedback,
        array $data,
        string $field,
        array $allowedValues,
        NavigationTabEnum $tab,
    ): void {
        $value = $data[$field] ?? null;
        if (is_scalar($value) && in_array((string) $value, $allowedValues, true)) {
            return;
        }

        $feedback->addError(
            error: t('The field "%s" contains an invalid option (%s).', $this->getFieldLabel($field), $tab->getName()),
            field: $field,
            tab: $tab->getHandle(),
        );
    }

    private function getFieldLabel(string $field): string
    {
        return match ($field) {
            'cacheBlockRecord' => t('Cache block record'),
            'cacheBlockOutput' => t('Cache block output'),
            'cacheBlockOutputOnPost' => t('Cache block output on post'),
            'cacheBlockOutputOnEditMode' => t('Cache block output in edit mode'),
            'cacheBlockOutputForRegisteredUsers' => t('Cache block output for registered users'),
            'supportSavingNullValues' => t('Support saving null values'),
            'ignorePageThemeGridFrameworkContainer' => t('Ignore page theme grid framework container'),
            'installBlock' => t('Install the block after creation'),
            'entriesAsFirstTab' => t('Entries as the first tab'),
            'highlightMultiElementFields' => t('Highlight multi-element fields'),
            'blockTypeSet' => t('Block type set'),
            default => $field,
        };
    }

    private function getStringKeys(array $options): array
    {
        return array_map('strval', array_keys($options));
    }
}
