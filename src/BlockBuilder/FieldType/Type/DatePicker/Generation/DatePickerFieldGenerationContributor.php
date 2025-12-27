<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\DatePicker\Generation;

use BlockBuilder\BlockGenerator\Exception\InvalidFieldGenerationDtoException;
use BlockBuilder\BlockGenerator\FileGenerator\Service\PhpLiteralFormatter;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContext;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContributorInterface;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerProperty;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerUseStatement;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseColumn;
use BlockBuilder\BlockGenerator\Generation\Plan\Enum\ControllerMethodSectionEnum;
use BlockBuilder\BlockGenerator\Generation\Plan\ViewGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\ViewVariableDocumentation;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\DatePicker\DatePickerFieldTypeDto;

final readonly class DatePickerFieldGenerationContributor implements FieldGenerationContributorInterface
{
    private const string GET_FORM_PARTS_METHOD = 'getBlockBuilderDateTimeFormParts';
    private const string NORMALIZE_METHOD = 'normalizeBlockBuilderDateTime';

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::DatePicker;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof DatePickerFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf('Date Picker field generation requires DTO "%s"; "%s" was provided.', DatePickerFieldTypeDto::class, $context->fieldDto::class));
        }

        $field = $context->fieldDto;
        $this->validateGenerationOptions($field);
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;

        $this->contributeSharedCode($planBuilder);
        $planBuilder->database->addColumn(
            $context->fieldContext,
            new DatabaseColumn(
                name: $field->handle,
                type: 'datetime',
                order: $context->position,
            ),
        );

        if ($context->isBasicField()) {
            $this->contributeBasicControllerCode($field, $fragmentKeyPrefix, $context->position, $planBuilder);
        } else {
            $this->contributeRepeatableControllerCode($field, $fragmentKeyPrefix, $context->position, $planBuilder);
            $planBuilder->form->addRepeatableDefaultValue($field->handle, '');
            if ($field->attachTimeSelector) {
                $planBuilder->form
                    ->addRepeatableDefaultValue($field->handle . '_hour', 0)
                    ->addRepeatableDefaultValue($field->handle . '_minute', 0);
            }
        }

        $planBuilder->view->addFieldVariable(
            $context->fieldContext,
            new ViewVariableDocumentation(
                name: $field->handle,
                type: 'string|null',
                description: sprintf('%s date and time', $field->label),
                order: $context->position,
            ),
        );
        $planBuilder->form->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderFormFragment($field, $context->isBasicField()),
                order: $context->position,
            ),
        );
        $planBuilder->view->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderViewFragment($field, $context->isBasicField()),
                order: $context->position,
            ),
        );
    }

    private function contributeSharedCode(BlockGenerationPlanBuilder $planBuilder): void
    {
        $planBuilder->controller
            ->addUseStatement(new ControllerUseStatement(\DateTimeImmutable::class))
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'date_picker.get_form_parts',
                    code: <<<'PHP'
private function getBlockBuilderDateTimeFormParts(mixed $value, bool $attachTimeSelector): array
{
    $parts = [
        'date' => '',
        'hour' => 0,
        'minute' => 0,
    ];
    if (!is_string($value) || trim($value) === '') {
        return $parts;
    }

    try {
        $dateTime = $attachTimeSelector
            ? $this->app->make('helper/date')->toDateTime($value, 'system', 'system')
            : new DateTimeImmutable($value);
    } catch (\Throwable) {
        return $parts;
    }
    if (!$dateTime instanceof \DateTimeInterface) {
        return $parts;
    }

    return [
        'date' => $dateTime->format('Y-m-d'),
        'hour' => (int) $dateTime->format('G'),
        'minute' => (int) $dateTime->format('i'),
    ];
}
PHP,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'date_picker.normalize',
                    code: <<<'PHP'
private function normalizeBlockBuilderDateTime(
    mixed $dateValue,
    mixed $hourValue,
    mixed $minuteValue,
    bool $attachTimeSelector,
    int $minuteInterval,
    string $minDate,
    string $maxDate,
): ?string {
    if (!is_string($dateValue)) {
        return null;
    }
    $dateValue = trim($dateValue);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $dateValue);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (
        !$date instanceof DateTimeImmutable
        || $date->format('Y-m-d') !== $dateValue
        || (is_array($dateErrors) && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
    ) {
        return null;
    }
    if (($minDate !== '' && $dateValue < $minDate) || ($maxDate !== '' && $dateValue > $maxDate)) {
        return null;
    }

    if (!$attachTimeSelector) {
        return $dateValue . ' 00:00:00';
    }
    if (
        (!is_int($hourValue) && !is_string($hourValue))
        || (is_string($hourValue) && preg_match('/^\d{1,2}$/D', $hourValue) !== 1)
        || (int) $hourValue < 0
        || (int) $hourValue > 23
        || (!is_int($minuteValue) && !is_string($minuteValue))
        || (is_string($minuteValue) && preg_match('/^\d{1,2}$/D', $minuteValue) !== 1)
        || (int) $minuteValue < 0
        || (int) $minuteValue > 59
        || (int) $minuteValue % $minuteInterval !== 0
    ) {
        return null;
    }

    $systemDateTime = sprintf(
        '%s %02d:%02d:00',
        $dateValue,
        (int) $hourValue,
        (int) $minuteValue,
    );
    $databaseDateTime = $this->app->make('helper/date')->toDB($systemDateTime, 'system');

    return is_string($databaseDateTime) && $databaseDateTime !== '' ? $databaseDateTime : null;
}
PHP,
                ),
            );

        $planBuilder->view->addFragment(
            ViewGenerationPlanBuilder::SECTION_SETUP,
            new CodeFragment(
                key: 'date_picker.date_service',
                code: <<<'PHP'
<?php
$dh = \Concrete\Core\Support\Facade\Application::getFacadeApplication()->make('helper/date');
/** @var \Concrete\Core\Localization\Service\Date $dh */
?>
PHP,
            ),
        );
    }

    private function contributeBasicControllerCode(
        DatePickerFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $planBuilder->controller
            ->addProperty(new ControllerProperty(
                name: $field->handle,
                declaration: sprintf('protected ?string $%s = null;', $field->handle),
                order: $position,
            ))
            ->addMethodFragment(
                ControllerMethodSectionEnum::AddEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderBasicAddEditCode($field, $handleLiteral),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::View->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: sprintf('$this->set(%s, $this->%s);', $handleLiteral, $field->handle),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::SaveBasicFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderSaveCode($field, '$args', '$args', true),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::ValidateBasicFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderValidationCode($field, '$args', false),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::PrepareBasicFieldsForComposerValidation->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderComposerValidationCode($field, '$args'),
                    order: $position,
                ),
            );
    }

    private function contributeRepeatableControllerCode(
        DatePickerFieldTypeDto $field,
        string $fragmentKeyPrefix,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $planBuilder->controller
            ->addMethodFragment(
                ControllerMethodSectionEnum::SaveEntryFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderSaveCode($field, '$data', '$entry', false),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::ValidateEntryFields->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderValidationCode($field, '$entry', true),
                    order: $position,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::PrepareEntryForEdit->value,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $this->renderRepeatableEditCode($field),
                    order: $position,
                ),
            );
    }

    private function renderBasicAddEditCode(DatePickerFieldTypeDto $field, string $handleLiteral): string
    {
        $lines = [
            sprintf(
                '$dateTimeParts = $this->%s($this->%s, %s);',
                self::GET_FORM_PARTS_METHOD,
                $field->handle,
                $field->attachTimeSelector ? 'true' : 'false',
            ),
            sprintf('$this->set(%s, $dateTimeParts[\'date\']);', $handleLiteral),
        ];
        if ($field->attachTimeSelector) {
            $lines[] = sprintf('$this->set(%s, $dateTimeParts[\'hour\']);', $this->phpLiteralFormatter->format($field->handle . '_hour'));
            $lines[] = sprintf('$this->set(%s, $dateTimeParts[\'minute\']);', $this->phpLiteralFormatter->format($field->handle . '_minute'));
        }

        return implode(PHP_EOL, $lines);
    }

    private function renderRepeatableEditCode(DatePickerFieldTypeDto $field): string
    {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $lines = [
            sprintf(
                '$dateTimeParts = $this->%s($entry[%s] ?? null, %s);',
                self::GET_FORM_PARTS_METHOD,
                $handleLiteral,
                $field->attachTimeSelector ? 'true' : 'false',
            ),
            sprintf('$entry[%s] = $dateTimeParts[\'date\'];', $handleLiteral),
        ];
        if ($field->attachTimeSelector) {
            $lines[] = sprintf('$entry[%s] = $dateTimeParts[\'hour\'];', $this->phpLiteralFormatter->format($field->handle . '_hour'));
            $lines[] = sprintf('$entry[%s] = $dateTimeParts[\'minute\'];', $this->phpLiteralFormatter->format($field->handle . '_minute'));
        }

        return implode(PHP_EOL, $lines);
    }

    private function renderComposerValidationCode(DatePickerFieldTypeDto $field, string $sourceVariable): string
    {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $lines = [
            sprintf(
                '$dateTimeParts = $this->%s(%s[%s] ?? null, %s);',
                self::GET_FORM_PARTS_METHOD,
                $sourceVariable,
                $handleLiteral,
                $field->attachTimeSelector ? 'true' : 'false',
            ),
            'if ($dateTimeParts[\'date\'] !== \'\') {',
            sprintf('    %s[%s] = $dateTimeParts[\'date\'];', $sourceVariable, $handleLiteral),
        ];
        if ($field->attachTimeSelector) {
            $lines[] = sprintf(
                '    %s[%s] = $dateTimeParts[\'hour\'];',
                $sourceVariable,
                $this->phpLiteralFormatter->format($field->handle . '_hour'),
            );
            $lines[] = sprintf(
                '    %s[%s] = $dateTimeParts[\'minute\'];',
                $sourceVariable,
                $this->phpLiteralFormatter->format($field->handle . '_minute'),
            );
        }
        $lines[] = '}';

        return implode(PHP_EOL, $lines);
    }

    private function renderSaveCode(
        DatePickerFieldTypeDto $field,
        string $targetVariable,
        string $sourceVariable,
        bool $removeTimeParts,
    ): string {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $hourLiteral = $this->phpLiteralFormatter->format($field->handle . '_hour');
        $minuteLiteral = $this->phpLiteralFormatter->format($field->handle . '_minute');
        $code = sprintf(
            '%1$s[%2$s] = $this->%3$s(%4$s[%2$s] ?? null, %4$s[%5$s] ?? null, %4$s[%6$s] ?? null, %7$s, %8$d, %9$s, %10$s);',
            $targetVariable,
            $handleLiteral,
            self::NORMALIZE_METHOD,
            $sourceVariable,
            $hourLiteral,
            $minuteLiteral,
            $field->attachTimeSelector ? 'true' : 'false',
            $field->minuteInterval,
            $this->phpLiteralFormatter->format($field->minDate),
            $this->phpLiteralFormatter->format($field->maxDate),
        );
        if ($removeTimeParts) {
            $code .= PHP_EOL . sprintf('unset(%s[%s], %s[%s]);', $targetVariable, $hourLiteral, $targetVariable, $minuteLiteral);
        }

        return $code;
    }

    private function renderValidationCode(
        DatePickerFieldTypeDto $field,
        string $sourceVariable,
        bool $repeatable,
    ): string {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $hourLiteral = $this->phpLiteralFormatter->format($field->handle . '_hour');
        $minuteLiteral = $this->phpLiteralFormatter->format($field->handle . '_minute');
        $label = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $requiredError = $repeatable
            ? sprintf('$errors->add(t(\'The field "%%s" is required in entry %%s.\', %s, $entryPosition + 1));', $label)
            : sprintf('$errors->add(t(\'The field "%%s" is required.\', %s));', $label);
        $invalidError = $repeatable
            ? sprintf('$errors->add(t(\'The field "%%s" in entry %%s contains an invalid date or time.\', %s, $entryPosition + 1));', $label)
            : sprintf('$errors->add(t(\'The field "%%s" contains an invalid date or time.\', %s));', $label);

        $lines = [
            sprintf('$dateValue = %s[%s] ?? null;', $sourceVariable, $handleLiteral),
            'if (is_scalar($dateValue)) {',
            '    $dateValue = trim((string) $dateValue);',
            '}',
            '$hasDateValue = $dateValue !== null && $dateValue !== \'\';',
        ];
        if ($field->required) {
            $lines[] = 'if (!$hasDateValue) {';
            $lines[] = '    ' . $requiredError;
            $lines[] = '} elseif ($this->' . self::NORMALIZE_METHOD . '(';
        } else {
            $lines[] = 'if (';
            $lines[] = '    $hasDateValue';
            $lines[] = '    &&';
            $lines[] = '    $this->' . self::NORMALIZE_METHOD . '(';
        }
        $argumentIndentation = $field->required ? '    ' : '        ';
        $closingIndentation = $field->required ? '' : '    ';
        $lines[] = $argumentIndentation . '$dateValue,';
        $lines[] = sprintf('%s%s[%s] ?? null,', $argumentIndentation, $sourceVariable, $hourLiteral);
        $lines[] = sprintf('%s%s[%s] ?? null,', $argumentIndentation, $sourceVariable, $minuteLiteral);
        $lines[] = sprintf('%s%s,', $argumentIndentation, $field->attachTimeSelector ? 'true' : 'false');
        $lines[] = sprintf('%s%d,', $argumentIndentation, $field->minuteInterval);
        $lines[] = sprintf('%s%s,', $argumentIndentation, $this->phpLiteralFormatter->format($field->minDate));
        $lines[] = sprintf('%s%s,', $argumentIndentation, $this->phpLiteralFormatter->format($field->maxDate));
        $lines[] = $closingIndentation . ') === null) {';
        $lines[] = '    ' . $invalidError;
        $lines[] = '}';

        return implode(PHP_EOL, $lines);
    }

    private function renderFormFragment(DatePickerFieldTypeDto $field, bool $basicField): string
    {
        return $this->stubRenderer->render(
            'fragments/date_picker/form-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            [
                '{{HANDLE}}' => $field->handle,
                '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
                '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
                '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
                '{{TIME_FIELDS}}' => $this->renderTimeFields($field, $basicField),
                '{{DATE_LIMIT_ATTRIBUTES}}' => $this->renderDateLimitAttributes($field),
                '{{HELP_TEXT}}' => $field->helpText === null || $field->helpText === ''
                    ? ''
                    : PHP_EOL . '    <div class="form-text"><?= t(' . $this->phpLiteralFormatter->format($field->helpText) . '); ?></div>',
            ],
        );
    }

    private function renderDateLimitAttributes(DatePickerFieldTypeDto $field): string
    {
        $attributes = [];
        if ($field->minDate !== '') {
            $attributes[] = '                min="<?= h(' . $this->phpLiteralFormatter->format($field->minDate) . '); ?>"';
        }
        if ($field->maxDate !== '') {
            $attributes[] = '                max="<?= h(' . $this->phpLiteralFormatter->format($field->maxDate) . '); ?>"';
        }

        return $attributes === [] ? '' : implode(PHP_EOL, $attributes) . PHP_EOL;
    }

    private function renderTimeFields(DatePickerFieldTypeDto $field, bool $basicField): string
    {
        if (!$field->attachTimeSelector) {
            return '';
        }

        return PHP_EOL . $this->stubRenderer->render(
            'fragments/date_picker/time-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            [
                '{{HANDLE}}' => $field->handle,
                '{{HOUR_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle . '_hour'),
                '{{MINUTE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle . '_minute'),
                '{{MINUTE_INTERVAL}}' => (string) $field->minuteInterval,
            ],
        );
    }

    private function renderViewFragment(DatePickerFieldTypeDto $field, bool $basicField): string
    {
        $valueExpression = $basicField
            ? '$' . $field->handle
            : '$entry[' . $this->phpLiteralFormatter->format($field->handle) . ']';
        if ($field->datePattern !== '') {
            $formatterCall = implode(PHP_EOL, [
                '$dh->formatCustom(',
                sprintf('        format: %s,', $this->phpLiteralFormatter->format($field->datePattern)),
                sprintf('        value: %s,', $valueExpression),
                '        toTimezone: \'system\',',
                '        fromTimezone: \'system\',',
                '    )',
            ]);
        } else {
            $formatterCall = implode(PHP_EOL, [
                '$dh->formatDate(',
                sprintf('        value: %s,', $valueExpression),
                '        format: false,',
                '        toTimezone: \'system\',',
                '    )',
            ]);
        }

        $replacements = ['{{FORMATTER_CALL}}' => $formatterCall];
        if ($basicField) {
            $replacements['{{HANDLE}}'] = $field->handle;
        } else {
            $replacements['{{HANDLE_LITERAL}}'] = $this->phpLiteralFormatter->format($field->handle);
        }

        return $this->stubRenderer->render(
            'fragments/date_picker/view-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            $replacements,
        );
    }

    private function validateGenerationOptions(DatePickerFieldTypeDto $field): void
    {
        foreach (['minimum' => $field->minDate, 'maximum' => $field->maxDate] as $limitName => $date) {
            $parsedDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if ($date !== '' && ($parsedDate === false || $parsedDate->format('Y-m-d') !== $date)) {
                throw new InvalidFieldGenerationDtoException(sprintf('Date Picker field "%s" requires a valid %s date.', $field->handle, $limitName));
            }
        }
        if ($field->minDate !== '' && $field->maxDate !== '' && $field->minDate > $field->maxDate) {
            throw new InvalidFieldGenerationDtoException(sprintf('Date Picker field "%s" requires its minimum date not to be later than its maximum date.', $field->handle));
        }
        if ($field->minuteInterval < 1 || $field->minuteInterval > 60 || 60 % $field->minuteInterval !== 0) {
            throw new InvalidFieldGenerationDtoException(sprintf('Date Picker field "%s" requires a minute interval that divides 60 without a remainder.', $field->handle));
        }
    }
}
