<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan;

use BlockBuilder\BlockGenerator\Exception\GenerationContributionConflictException;
use BlockBuilder\BlockGenerator\Generation\Plan\Support\SectionedCodeFragmentBuilder;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;

final class FormGenerationPlanBuilder
{
    public const string SECTION_SETUP = 'setup';
    public const string SECTION_BASIC_FIELDS = 'basic_fields';
    public const string SECTION_REPEATABLE_FIELDS = 'repeatable_fields';
    public const string SECTION_SETTINGS = 'settings';
    public const string SECTION_VALIDATION = 'validation';

    private const array RESERVED_REPEATABLE_CAPTURE_NAMES = [
        'GLOBALS',
        '_COOKIE',
        '_ENV',
        '_FILES',
        '_GET',
        '_POST',
        '_REQUEST',
        '_SERVER',
        '_SESSION',
        'blockBuilderEntries',
        'entry',
        'entryIndex',
        'form',
        'renderBlockBuilderEntry',
        'this',
        'translatedAddAtBottomLabel',
        'translatedAddAtTopLabel',
        'translatedCollapseAllLabel',
        'translatedConfirmLabel',
        'translatedCopyLastLabel',
        'translatedDisableSmoothScrollLabel',
        'translatedDuplicateLabel',
        'translatedDuplicateAtEndLabel',
        'translatedExpandAllLabel',
        'translatedKeepAddedCollapsedLabel',
        'translatedMaximumLabel',
        'translatedMoveLabel',
        'translatedNoEntriesLabel',
        'translatedRemoveAllLabel',
        'translatedRemoveLabel',
        'view',
    ];

    private SectionedCodeFragmentBuilder $fragments;

    /**
     * @var array<string, mixed>
     */
    private array $repeatableDefaultValues = [];

    /**
     * @var array<string, true>
     */
    private array $repeatableCapturedVariableNames = [];

    public function __construct()
    {
        $this->fragments = new SectionedCodeFragmentBuilder('form fragments');
    }

    public function addFieldFragment(FieldTypeContextEnum $context, CodeFragment $fragment): self
    {
        return $this->addFragment(
            $context === FieldTypeContextEnum::BasicFields
                ? self::SECTION_BASIC_FIELDS
                : self::SECTION_REPEATABLE_FIELDS,
            $fragment,
        );
    }

    public function addSettingsFragment(CodeFragment $fragment): self
    {
        return $this->addFragment(self::SECTION_SETTINGS, $fragment);
    }

    public function addRepeatableDefaultValue(string $fieldHandle, mixed $value): self
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $fieldHandle)) {
            throw new \InvalidArgumentException(sprintf('Invalid repeatable default-value field handle "%s".', $fieldHandle));
        }

        if (
            array_key_exists($fieldHandle, $this->repeatableDefaultValues)
            && $this->repeatableDefaultValues[$fieldHandle] !== $value
        ) {
            throw new GenerationContributionConflictException(sprintf('Conflicting repeatable default values were provided for field "%s".', $fieldHandle));
        }

        $this->repeatableDefaultValues[$fieldHandle] = $value;

        return $this;
    }

    /**
     * Exposes a setup variable to the repeatable-field rendering closure.
     *
     * The generator always exposes $form and $view. Contributors that create any other variable in
     * SECTION_SETUP must also register its name here, without the leading dollar sign.
     */
    public function addRepeatableCapturedVariable(string $variableName): self
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $variableName) !== 1) {
            throw new \InvalidArgumentException(sprintf('Invalid repeatable-form captured variable name "%s". Pass the variable name without a leading dollar sign.', $variableName));
        }

        if (in_array($variableName, self::RESERVED_REPEATABLE_CAPTURE_NAMES, true)) {
            throw new GenerationContributionConflictException(sprintf('Repeatable-form captured variable "%s" conflicts with PHP or generated renderer state.', $variableName));
        }

        $this->repeatableCapturedVariableNames[$variableName] = true;

        return $this;
    }

    public function addFragment(string $section, CodeFragment $fragment): self
    {
        if (!in_array($section, [
            self::SECTION_SETUP,
            self::SECTION_BASIC_FIELDS,
            self::SECTION_REPEATABLE_FIELDS,
            self::SECTION_SETTINGS,
            self::SECTION_VALIDATION,
        ], true)) {
            throw new \InvalidArgumentException(sprintf('Unknown form generation section "%s".', $section));
        }

        $this->fragments->add($section, $fragment);

        return $this;
    }

    public function build(): FormGenerationPlan
    {
        $repeatableCapturedVariableNames = array_keys($this->repeatableCapturedVariableNames);
        sort($repeatableCapturedVariableNames, SORT_STRING);

        return new FormGenerationPlan(
            fragmentsBySection: $this->fragments->build(),
            repeatableDefaultValues: $this->repeatableDefaultValues,
            repeatableCapturedVariableNames: $repeatableCapturedVariableNames,
        );
    }
}
