<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\FlexLink\Generation;

use BlockBuilder\BlockGenerator\Exception\InvalidFieldGenerationDtoException;
use BlockBuilder\BlockGenerator\FileGenerator\Service\PhpLiteralFormatter;
use BlockBuilder\BlockGenerator\FileGenerator\Service\StubRenderer;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContext;
use BlockBuilder\BlockGenerator\Generation\FieldGenerationContributorInterface;
use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerProperty;
use BlockBuilder\BlockGenerator\Generation\Plan\DatabaseColumn;
use BlockBuilder\BlockGenerator\Generation\Plan\Enum\ControllerMethodSectionEnum;
use BlockBuilder\BlockGenerator\Generation\Plan\ViewVariableDocumentation;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Generation\LinkFieldGenerationSupport;
use BlockBuilder\FieldType\Type\FlexLink\FlexLinkFieldTypeDto;

final readonly class FlexLinkFieldGenerationContributor implements FieldGenerationContributorInterface
{
    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
        private LinkFieldGenerationSupport $linkFieldGenerationSupport,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::FlexLink;
    }

    public function contribute(
        FieldGenerationContext $context,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        if (!$context->fieldDto instanceof FlexLinkFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Flex Link field generation requires DTO "%s"; "%s" was provided.',
                FlexLinkFieldTypeDto::class,
                $context->fieldDto::class,
            ));
        }

        $field = $context->fieldDto;
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);

        $this->linkFieldGenerationSupport->contributeControllerCode($planBuilder);
        $planBuilder->database->addColumn(
            $context->fieldContext,
            new DatabaseColumn(
                name: $field->handle,
                type: 'text',
                order: $context->position,
            ),
        );
        $planBuilder->controller
            ->addRequiredFeature('PAGES')
            ->addRequiredFeature('FILES');

        if ($context->isBasicField()) {
            $this->contributeBasicControllerCode(
                field: $field,
                fragmentKeyPrefix: $fragmentKeyPrefix,
                handleLiteral: $handleLiteral,
                position: $context->position,
                planBuilder: $planBuilder,
            );
        } else {
            $this->contributeRepeatableControllerCode(
                field: $field,
                fragmentKeyPrefix: $fragmentKeyPrefix,
                handleLiteral: $handleLiteral,
                position: $context->position,
                planBuilder: $planBuilder,
            );
            $planBuilder->form->addRepeatableDefaultValue(
                $field->handle,
                $this->getDefaultLinkData(),
            );
        }

        $viewVariables = [
            '_link' => sprintf('Resolved URL for %s', $field->label),
            '_ending' => sprintf('URL suffix for %s', $field->label),
            '_text' => sprintf('Link text for %s', $field->label),
            '_title' => sprintf('Title attribute for %s', $field->label),
            '_new_window' => sprintf('Generated target attribute for %s', $field->label),
            '_no_follow' => sprintf('Generated rel attribute for %s', $field->label),
        ];
        $documentationOffset = 0;
        foreach ($viewVariables as $suffix => $description) {
            $planBuilder->view->addFieldVariable(
                $context->fieldContext,
                new ViewVariableDocumentation(
                    name: $field->handle . $suffix,
                    type: 'string',
                    description: $description,
                    order: ($context->position * 10) + $documentationOffset,
                ),
            );
            $documentationOffset++;
        }

        $planBuilder->form->addFieldFragment(
            $context->fieldContext,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderFormFragment($context),
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

    private function contributeBasicControllerCode(
        FlexLinkFieldTypeDto $field,
        string $fragmentKeyPrefix,
        string $handleLiteral,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $planBuilder->controller->addProperty(new ControllerProperty(
            name: $field->handle,
            declaration: sprintf('protected ?string $%s = null;', $field->handle),
            order: $position,
        ));
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::AddEdit->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$this->set(%1$s, $this->%2$s($this->%3$s ?? \'\'));',
                    $handleLiteral,
                    LinkFieldGenerationSupport::NORMALIZE_METHOD,
                    $field->handle,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::View->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderBasicViewPreparation($field),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::SaveBasicFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$args[%1$s] = $this->%2$s($args[%1$s] ?? []);',
                    $handleLiteral,
                    LinkFieldGenerationSupport::ENCODE_METHOD,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::ValidateBasicFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderValidation($field, '$args[' . $handleLiteral . '] ?? null'),
                order: $position,
            ),
        );
        $planBuilder->controller->addFileUsageFragment(
            FieldTypeContextEnum::BasicFields,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$linkData = $this->%1$s($this->%2$s ?? \'\');%3$sif ($linkData[\'link_type\'] === \'link_from_file_manager\' && $linkData[\'link_from_file_manager\'] > 0) {%3$s    $files[] = $linkData[\'link_from_file_manager\'];%3$s}',
                    LinkFieldGenerationSupport::NORMALIZE_METHOD,
                    $field->handle,
                    PHP_EOL,
                ),
                order: $position,
            ),
        );
    }

    private function contributeRepeatableControllerCode(
        FlexLinkFieldTypeDto $field,
        string $fragmentKeyPrefix,
        string $handleLiteral,
        int $position,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::SaveEntryFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$data[%1$s] = $this->%2$s($entry[%1$s] ?? []);',
                    $handleLiteral,
                    LinkFieldGenerationSupport::ENCODE_METHOD,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::ValidateEntryFields->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderValidation(
                    $field,
                    '$entry[' . $handleLiteral . '] ?? null',
                    repeatable: true,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::PrepareEntryForEdit->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$entry[%1$s] = $this->%2$s($entry[%1$s] ?? \'\');',
                    $handleLiteral,
                    LinkFieldGenerationSupport::NORMALIZE_METHOD,
                ),
                order: $position,
            ),
        );
        $planBuilder->controller->addMethodFragment(
            ControllerMethodSectionEnum::PrepareEntryForView->value,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderRepeatableViewPreparation($field, $handleLiteral),
                order: $position,
            ),
        );
        $planBuilder->controller->addFileUsageFragment(
            FieldTypeContextEnum::RepeatableFields,
            new CodeFragment(
                key: $fragmentKeyPrefix,
                code: sprintf(
                    '$linkData = $this->%1$s($entry[%2$s] ?? \'\');%3$sif ($linkData[\'link_type\'] === \'link_from_file_manager\' && $linkData[\'link_from_file_manager\'] > 0) {%3$s    $files[] = $linkData[\'link_from_file_manager\'];%3$s}',
                    LinkFieldGenerationSupport::NORMALIZE_METHOD,
                    $handleLiteral,
                    PHP_EOL,
                ),
                order: $position,
            ),
        );
    }

    private function renderValidation(
        FlexLinkFieldTypeDto $field,
        string $valueExpression,
        bool $repeatable = false,
    ): string {
        $label = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        $position = $repeatable ? ', $entryPosition + 1' : '';
        $suffix = $repeatable ? ' in entry %s' : '';

        return sprintf(
            '$linkValidationError = $this->%1$s(%2$s, %3$s);%4$s'
            . 'if ($linkValidationError === \'required\') {%4$s'
            . '    $errors->add(t(\'The field "%%s" is required%5$s.\', %6$s%7$s));%4$s'
            . '} elseif ($linkValidationError === \'missing_destination\') {%4$s'
            . '    $errors->add(t(\'The field "%%s" requires a link destination%5$s.\', %6$s%7$s));%4$s'
            . '} elseif ($linkValidationError === \'too_long\') {%4$s'
            . '    $errors->add(t(\'The field "%%s" contains a value longer than 255 characters%5$s.\', %6$s%7$s));%4$s'
            . '} elseif ($linkValidationError !== null) {%4$s'
            . '    $errors->add(t(\'The field "%%s" contains an invalid link%5$s.\', %6$s%7$s));%4$s'
            . '}',
            LinkFieldGenerationSupport::VALIDATE_METHOD,
            $valueExpression,
            $field->required ? 'true' : 'false',
            PHP_EOL,
            $suffix,
            $label,
            $position,
        );
    }

    private function renderBasicViewPreparation(FlexLinkFieldTypeDto $field): string
    {
        $handle = $field->handle;

        return sprintf(
            '$linkData = $this->%1$s($this->%2$s ?? \'\');%3$s'
            . '$this->set(%4$s, $linkData[\'url\']);%3$s'
            . '$this->set(%5$s, $linkData[\'ending\']);%3$s'
            . '$this->set(%6$s, $linkData[\'text\']);%3$s'
            . '$this->set(%7$s, $linkData[\'title\']);%3$s'
            . '$this->set(%8$s, $linkData[\'new_window\'] ? \'target="_blank"\' : \'\');%3$s'
            . '$this->set(%9$s, $linkData[\'no_follow\'] ? \'rel="nofollow"\' : \'\');',
            LinkFieldGenerationSupport::RESOLVE_METHOD,
            $handle,
            PHP_EOL,
            $this->phpLiteralFormatter->format($handle . '_link'),
            $this->phpLiteralFormatter->format($handle . '_ending'),
            $this->phpLiteralFormatter->format($handle . '_text'),
            $this->phpLiteralFormatter->format($handle . '_title'),
            $this->phpLiteralFormatter->format($handle . '_new_window'),
            $this->phpLiteralFormatter->format($handle . '_no_follow'),
        );
    }

    private function renderRepeatableViewPreparation(
        FlexLinkFieldTypeDto $field,
        string $handleLiteral,
    ): string {
        $handle = $field->handle;

        return sprintf(
            '$linkData = $this->%1$s($entry[%2$s] ?? \'\');%3$s'
            . '$entry[%4$s] = $linkData[\'url\'];%3$s'
            . '$entry[%5$s] = $linkData[\'ending\'];%3$s'
            . '$entry[%6$s] = $linkData[\'text\'];%3$s'
            . '$entry[%7$s] = $linkData[\'title\'];%3$s'
            . '$entry[%8$s] = $linkData[\'new_window\'] ? \'target="_blank"\' : \'\';%3$s'
            . '$entry[%9$s] = $linkData[\'no_follow\'] ? \'rel="nofollow"\' : \'\';',
            LinkFieldGenerationSupport::RESOLVE_METHOD,
            $handleLiteral,
            PHP_EOL,
            $this->phpLiteralFormatter->format($handle . '_link'),
            $this->phpLiteralFormatter->format($handle . '_ending'),
            $this->phpLiteralFormatter->format($handle . '_text'),
            $this->phpLiteralFormatter->format($handle . '_title'),
            $this->phpLiteralFormatter->format($handle . '_new_window'),
            $this->phpLiteralFormatter->format($handle . '_no_follow'),
        );
    }

    private function renderFormFragment(FieldGenerationContext $context): string
    {
        $field = $context->fieldDto;
        if (!$field instanceof FlexLinkFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException('Flex Link form rendering received an invalid DTO.');
        }
        $config = $context->config;
        $replacements = [
            '{{HANDLE}}' => $field->handle,
            '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
            '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
            '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? " . ' *'" : '',
            '{{HELP_TEXT}}' => $this->renderHelpText($field->helpText),
            '{{SITEMAP_LABEL_LITERAL}}' => $this->formatLabel($config->linkFromSitemapLabel, 'Link from Sitemap'),
            '{{FILE_LABEL_LITERAL}}' => $this->formatLabel($config->linkFromFileManagerLabel, 'Link from File Manager'),
            '{{EXTERNAL_LABEL_LITERAL}}' => $this->formatLabel($config->externalLinkLabel, 'External Link'),
            '{{SHOW_ADDITIONAL_LABEL_LITERAL}}' => $this->formatLabel($config->showAdditionalFieldsLabel, 'Show additional fields'),
            '{{HIDE_ADDITIONAL_LABEL_LITERAL}}' => $this->formatLabel($config->hideAdditionalFieldsLabel, 'Hide additional fields'),
            '{{ENDING_LABEL_LITERAL}}' => $this->formatLabel($config->urlEndingLabel, 'Custom string at the end of URL'),
            '{{ENDING_HELP_LITERAL}}' => $this->formatLabel($config->urlEndingHelpTextLabel, '(e.g. #contact-form or ?ccm_paging_p=2)'),
            '{{TEXT_LABEL_LITERAL}}' => $this->formatLabel($config->textLabel, 'Text'),
            '{{TITLE_LABEL_LITERAL}}' => $this->formatLabel($config->titleLabel, 'Title'),
            '{{NEW_WINDOW_LABEL_LITERAL}}' => $this->formatLabel($config->newWindowLabel, 'Open in new window'),
            '{{NO_FOLLOW_LABEL_LITERAL}}' => $this->formatLabel($config->noFollowLabel, 'Add nofollow attribute'),
            '{{YES_LABEL_LITERAL}}' => $this->formatLabel($config->yesLabel, 'Yes'),
            '{{NO_LABEL_LITERAL}}' => $this->formatLabel($config->noLabel, 'No'),
        ];

        return $this->stubRenderer->render(
            $context->isBasicField()
                ? 'fragments/flex_link/form-basic.php.stub'
                : 'fragments/flex_link/form-repeatable.php.stub',
            $replacements,
        );
    }

    private function renderViewFragment(FlexLinkFieldTypeDto $field, bool $basic): string
    {
        $replacements = $basic
            ? ['{{HANDLE}}' => $field->handle]
            : [
                '{{LINK_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle . '_link'),
                '{{ENDING_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle . '_ending'),
                '{{TEXT_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle . '_text'),
                '{{TITLE_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle . '_title'),
                '{{NEW_WINDOW_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle . '_new_window'),
                '{{NO_FOLLOW_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle . '_no_follow'),
            ];

        return $this->stubRenderer->render(
            $basic
                ? 'fragments/flex_link/view-basic.php.stub'
                : 'fragments/flex_link/view-repeatable.php.stub',
            $replacements,
        );
    }

    private function formatLabel(?string $label, string $fallback): string
    {
        return $this->phpLiteralFormatter->format(trim((string) $label) ?: $fallback);
    }

    private function renderHelpText(?string $helpText): string
    {
        if ($helpText === null || trim($helpText) === '') {
            return '';
        }

        return PHP_EOL . sprintf(
            '    <div class="form-text"><?= t(%s); ?></div>',
            $this->phpLiteralFormatter->format($helpText),
        );
    }

    private function getDefaultLinkData(): array
    {
        return [
            'link_type' => '',
            'show_additional_fields' => 0,
            'link_from_sitemap' => 0,
            'link_from_file_manager' => 0,
            'protocol' => 'https://',
            'external_link' => '',
            'ending' => '',
            'text' => '',
            'title' => '',
            'new_window' => 0,
            'no_follow' => 0,
        ];
    }
}
