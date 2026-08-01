<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\Image\Generation;

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
use BlockBuilder\BlockGenerator\Generation\Plan\ViewVariableDocumentation;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\Image\ImageFieldTypeDto;
use Concrete\Core\File\File;
use Concrete\Core\File\Type\Type;

final readonly class ImageFieldGenerationContributor implements FieldGenerationContributorInterface
{
    private const string DECODE_DATA_METHOD = 'decodeBlockBuilderImageData';
    private const string NORMALIZE_DATA_METHOD = 'normalizeBlockBuilderImageData';
    private const string VALIDATE_FILE_METHOD = 'isValidBlockBuilderImage';
    private const string RESOLVE_IMAGE_METHOD = 'resolveBlockBuilderImage';

    public function __construct(
        private StubRenderer $stubRenderer,
        private PhpLiteralFormatter $phpLiteralFormatter,
    ) {
    }

    public function getFieldType(): FieldTypeEnum
    {
        return FieldTypeEnum::Image;
    }

    public function contribute(FieldGenerationContext $context, BlockGenerationPlanBuilder $planBuilder): void
    {
        if (!$context->fieldDto instanceof ImageFieldTypeDto) {
            throw new InvalidFieldGenerationDtoException(sprintf(
                'Image field generation requires DTO "%s"; "%s" was provided.',
                ImageFieldTypeDto::class,
                $context->fieldDto::class,
            ));
        }

        $field = $context->fieldDto;
        $fragmentKeyPrefix = $context->fieldContext->value . '.' . $field->handle;

        $this->contributeSharedControllerCode($planBuilder);
        $this->contributeDatabaseColumns($context, $field, $planBuilder);
        $this->contributeControllerMetadata($context, $field, $fragmentKeyPrefix, $planBuilder);

        if ($context->isBasicField()) {
            $this->contributeBasicControllerCode($context, $field, $fragmentKeyPrefix, $planBuilder);
        } else {
            $this->contributeRepeatableControllerCode($context, $field, $fragmentKeyPrefix, $planBuilder);
            $planBuilder->form
                ->addRepeatableDefaultValue($field->handle, 0)
                ->addRepeatableDefaultValue($field->handle . '_data', '{}');
            if ($field->showAltTextField) {
                $planBuilder->form->addRepeatableDefaultValue($field->handle . '_alt', '');
            }
            foreach ($this->getImageDataKeys($field) as $imageDataKey) {
                $planBuilder->form->addRepeatableDefaultValue($field->handle . '_' . $imageDataKey, 0);
            }
        }

        $this->contributeViewDocumentation($context, $field, $planBuilder);
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

        if ($context->isRepeatableField() && ($field->thumbnailEditable || $field->fullscreenEditable)) {
            $this->contributeEditableSettings($context, $field, $fragmentKeyPrefix, $planBuilder);
        }
    }

    private function contributeSharedControllerCode(BlockGenerationPlanBuilder $planBuilder): void
    {
        $planBuilder->controller
            ->addUseStatement(new ControllerUseStatement(File::class))
            ->addUseStatement(new ControllerUseStatement(Type::class, 'FileType'))
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'image.decode_data',
                    code: <<<'PHP'
private function decodeBlockBuilderImageData(mixed $value): array
{
    if (is_array($value)) {
        return $value;
    }
    if (!is_string($value) || trim($value) === '') {
        return [];
    }

    $decodedValue = json_decode($value, true);

    return is_array($decodedValue) ? $decodedValue : [];
}
PHP,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'image.normalize_data',
                    code: <<<'PHP'
private function normalizeBlockBuilderImageData(mixed $value): array
{
    $data = $this->decodeBlockBuilderImageData($value);

    return [
        'show_additional_fields' => !empty($data['show_additional_fields']) ? 1 : 0,
        'override_dimensions' => !empty($data['override_dimensions']) ? 1 : 0,
        'custom_width' => isset($data['custom_width']) && is_numeric($data['custom_width']) ? max(0, (int) $data['custom_width']) : 0,
        'custom_height' => isset($data['custom_height']) && is_numeric($data['custom_height']) ? max(0, (int) $data['custom_height']) : 0,
        'custom_crop' => !empty($data['custom_crop']) ? 1 : 0,
        'override_fullscreen_dimensions' => !empty($data['override_fullscreen_dimensions']) ? 1 : 0,
        'custom_fullscreen_width' => isset($data['custom_fullscreen_width']) && is_numeric($data['custom_fullscreen_width']) ? max(0, (int) $data['custom_fullscreen_width']) : 0,
        'custom_fullscreen_height' => isset($data['custom_fullscreen_height']) && is_numeric($data['custom_fullscreen_height']) ? max(0, (int) $data['custom_fullscreen_height']) : 0,
        'custom_fullscreen_crop' => !empty($data['custom_fullscreen_crop']) ? 1 : 0,
    ];
}
PHP,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'image.validate_file',
                    code: <<<'PHP'
private function isValidBlockBuilderImage(mixed $fileID): bool
{
    if (!is_scalar($fileID) || (int) $fileID < 1) {
        return false;
    }

    $file = File::getByID((int) $fileID);
    $fileVersion = is_object($file) ? $file->getApprovedVersion() : null;

    return is_object($fileVersion)
        && $fileVersion->getTypeObject()->getGenericType() === FileType::T_IMAGE;
}
PHP,
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'image.resolve',
                    code: <<<'PHP'
private function resolveBlockBuilderImage(mixed $fileID, mixed $altText, array $options): array
{
    $resolvedImage = [
        'fileID' => is_scalar($fileID) ? max(0, (int) $fileID) : 0,
        'alt' => is_scalar($altText) ? trim((string) $altText) : '',
        'object' => false,
        'filename' => '',
        'type' => '',
        'relativePath' => '',
        'link' => '',
        'width' => 0,
        'height' => 0,
        'fullscreenLink' => '',
        'fullscreenWidth' => 0,
        'fullscreenHeight' => 0,
        'thumbnailLink' => '',
        'thumbnailWidth' => 0,
        'thumbnailHeight' => 0,
    ];
    if (!$this->isValidBlockBuilderImage($resolvedImage['fileID'])) {
        return $resolvedImage;
    }

    $file = File::getByID($resolvedImage['fileID']);
    $fileVersion = $file->getApprovedVersion();
    $resolvedImage['object'] = $file;
    $resolvedImage['filename'] = (string) $fileVersion->getFileName();
    $resolvedImage['type'] = (string) $fileVersion->getType();
    $resolvedImage['relativePath'] = (string) $fileVersion->getRelativePath();
    $resolvedImage['link'] = (string) $fileVersion->getURL();
    $resolvedImage['width'] = max(0, (int) $fileVersion->getAttribute('width'));
    $resolvedImage['height'] = max(0, (int) $fileVersion->getAttribute('height'));

    if ($resolvedImage['alt'] === '') {
        $resolvedImage['alt'] = (string) $fileVersion->getTitle();
        $extension = strtolower(pathinfo($resolvedImage['alt'], PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'tif', 'tiff', 'svg', 'webp', 'avif'], true)) {
            $resolvedImage['alt'] = (string) pathinfo($resolvedImage['alt'], PATHINFO_FILENAME);
            $resolvedImage['alt'] = (string) preg_replace('/ - [0-9]+$/', '', $resolvedImage['alt']);
        }
    }

    foreach (['thumbnail', 'fullscreen'] as $variant) {
        if (empty($options[$variant])) {
            continue;
        }
        $width = isset($options[$variant . 'Width']) && is_numeric($options[$variant . 'Width'])
            ? max(0, (int) $options[$variant . 'Width'])
            : 0;
        $height = isset($options[$variant . 'Height']) && is_numeric($options[$variant . 'Height'])
            ? max(0, (int) $options[$variant . 'Height'])
            : 0;
        $shouldResize = ($width > 0 && $resolvedImage['width'] > $width)
            || ($height > 0 && $resolvedImage['height'] > $height);
        if ($shouldResize) {
            $thumbnail = $this->app->make('helper/image')->getThumbnail(
                $file,
                $width > 0 ? $width : null,
                $height > 0 ? $height : null,
                !empty($options[$variant . 'Crop']),
            );
            $resolvedImage[$variant . 'Link'] = (string) $thumbnail->src;
            $resolvedImage[$variant . 'Width'] = (int) $thumbnail->width;
            $resolvedImage[$variant . 'Height'] = (int) $thumbnail->height;
        } else {
            $resolvedImage[$variant . 'Link'] = $resolvedImage['link'];
            $resolvedImage[$variant . 'Width'] = $resolvedImage['width'];
            $resolvedImage[$variant . 'Height'] = $resolvedImage['height'];
        }
    }

    return $resolvedImage;
}
PHP,
                ),
            );
    }

    private function contributeDatabaseColumns(
        FieldGenerationContext $context,
        ImageFieldTypeDto $field,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $columns = [
            new DatabaseColumn(
                name: $field->handle,
                type: 'integer',
                unsigned: true,
                hasDefault: true,
                defaultValue: 0,
                order: $context->position,
            ),
            new DatabaseColumn(
                name: $field->handle . '_data',
                type: 'text',
                order: $context->position,
            ),
        ];
        if ($field->showAltTextField) {
            $columns[] = new DatabaseColumn(
                name: $field->handle . '_alt',
                type: 'string',
                size: '255',
                order: $context->position,
            );
        }
        foreach ($columns as $column) {
            $planBuilder->database->addColumn($context->fieldContext, $column);
        }

        if ($context->isRepeatableField() && ($field->thumbnailEditable || $field->fullscreenEditable)) {
            $planBuilder->database->addColumn(
                FieldTypeContextEnum::BasicFields,
                new DatabaseColumn(name: 'settings', type: 'text', order: -100),
            );
        }
    }

    private function contributeControllerMetadata(
        FieldGenerationContext $context,
        ImageFieldTypeDto $field,
        string $fragmentKeyPrefix,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $planBuilder->controller
            ->addRequiredFeature('FILES')
            ->addExportFileColumn($field->handle)
            ->addFileUsageFragment(
                $context->fieldContext,
                new CodeFragment(
                    key: $fragmentKeyPrefix,
                    code: $context->isBasicField()
                        ? sprintf(
                            'if ((int) ($this->%1$s ?? 0) > 0) {%2$s    $files[] = (int) $this->%1$s;%2$s}',
                            $field->handle,
                            PHP_EOL,
                        )
                        : sprintf(
                            'if ((int) ($entry[%1$s] ?? 0) > 0) {%2$s    $files[] = (int) $entry[%1$s];%2$s}',
                            $this->phpLiteralFormatter->format($field->handle),
                            PHP_EOL,
                        ),
                    order: $context->position,
                ),
            );
    }

    private function contributeBasicControllerCode(
        FieldGenerationContext $context,
        ImageFieldTypeDto $field,
        string $fragmentKeyPrefix,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $dataHandle = $field->handle . '_data';
        $dataHandleLiteral = $this->phpLiteralFormatter->format($dataHandle);
        $altHandle = $field->handle . '_alt';
        $altHandleLiteral = $this->phpLiteralFormatter->format($altHandle);

        $planBuilder->controller
            ->addProperty(new ControllerProperty(
                name: $field->handle,
                declaration: sprintf('protected int|string|null $%s = null;', $field->handle),
                order: $context->position,
            ))
            ->addProperty(new ControllerProperty(
                name: $dataHandle,
                declaration: sprintf('protected ?string $%s = null;', $dataHandle),
                order: $context->position,
            ));
        if ($field->showAltTextField) {
            $planBuilder->controller->addProperty(new ControllerProperty(
                name: $altHandle,
                declaration: sprintf('protected ?string $%s = null;', $altHandle),
                order: $context->position,
            ));
        }

        $addEditLines = [sprintf(
            '$this->set(%1$s, (int) ($this->%2$s ?? 0));',
            $handleLiteral,
            $field->handle,
        )];
        if ($field->showAltTextField) {
            $addEditLines[] = sprintf('$this->set(%s, $this->%s ?? \'\');', $altHandleLiteral, $altHandle);
        }
        $addEditLines[] = sprintf('$this->set(%s, $this->%s ?? \'{}\');', $dataHandleLiteral, $dataHandle);
        $addEditLines[] = sprintf('$imageData = $this->%s($this->%s ?? []);', self::NORMALIZE_DATA_METHOD, $dataHandle);
        foreach ($this->getImageDataKeys($field) as $imageDataKey) {
            $addEditLines[] = sprintf(
                '$this->set(%1$s, $imageData[%2$s]);',
                $this->phpLiteralFormatter->format($field->handle . '_' . $imageDataKey),
                $this->phpLiteralFormatter->format($imageDataKey),
            );
        }

        $saveLines = [sprintf(
            '$args[%1$s] = isset($args[%1$s]) && is_scalar($args[%1$s]) ? max(0, (int) $args[%1$s]) : 0;',
            $handleLiteral,
        )];
        if ($field->showAltTextField) {
            $saveLines[] = sprintf(
                '$args[%1$s] = isset($args[%1$s]) && is_scalar($args[%1$s]) ? mb_substr(trim((string) $args[%1$s]), 0, 255) : \'\';',
                $altHandleLiteral,
            );
        }
        $saveLines[] = sprintf(
            '$args[%1$s] = json_encode($this->%2$s(%3$s), JSON_THROW_ON_ERROR);',
            $dataHandleLiteral,
            self::NORMALIZE_DATA_METHOD,
            $this->renderSubmittedImageDataArray($field, '$args'),
        );

        $validationCode = $this->renderFileValidation($field, '$args[' . $handleLiteral . '] ?? null');
        if ($field->thumbnailEditable || $field->fullscreenEditable) {
            $validationCode .= PHP_EOL . PHP_EOL . $this->renderInlineDimensionsValidationCode(
                $field,
                '$args',
            );
        }

        $planBuilder->controller
            ->addMethodFragment(ControllerMethodSectionEnum::AddEdit->value, new CodeFragment(
                key: $fragmentKeyPrefix,
                code: implode(PHP_EOL, $addEditLines),
                order: $context->position,
            ))
            ->addMethodFragment(ControllerMethodSectionEnum::SaveBasicFields->value, new CodeFragment(
                key: $fragmentKeyPrefix,
                code: implode(PHP_EOL, $saveLines),
                order: $context->position,
            ))
            ->addMethodFragment(ControllerMethodSectionEnum::ValidateBasicFields->value, new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $validationCode,
                order: $context->position,
            ))
            ->addMethodFragment(ControllerMethodSectionEnum::View->value, new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderBasicViewPreparation($field),
                order: $context->position,
            ));
    }

    private function contributeRepeatableControllerCode(
        FieldGenerationContext $context,
        ImageFieldTypeDto $field,
        string $fragmentKeyPrefix,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $altHandleLiteral = $this->phpLiteralFormatter->format($field->handle . '_alt');
        $dataHandleLiteral = $this->phpLiteralFormatter->format($field->handle . '_data');

        $saveLines = [sprintf(
            '$data[%1$s] = isset($entry[%1$s]) && is_scalar($entry[%1$s]) ? max(0, (int) $entry[%1$s]) : 0;',
            $handleLiteral,
        )];
        if ($field->showAltTextField) {
            $saveLines[] = sprintf(
                '$data[%1$s] = isset($entry[%1$s]) && is_scalar($entry[%1$s]) ? mb_substr(trim((string) $entry[%1$s]), 0, 255) : \'\';',
                $altHandleLiteral,
            );
        }
        $saveLines[] = sprintf(
            '$data[%1$s] = json_encode($this->%2$s(%3$s), JSON_THROW_ON_ERROR);',
            $dataHandleLiteral,
            self::NORMALIZE_DATA_METHOD,
            $this->renderSubmittedImageDataArray($field, '$entry'),
        );

        $prepareEditLines = [sprintf(
            '$entry[%1$s] = $this->%2$s($entry[%1$s] ?? null) ? (int) $entry[%1$s] : 0;',
            $handleLiteral,
            self::VALIDATE_FILE_METHOD,
        )];
        if ($field->showAltTextField) {
            $prepareEditLines[] = sprintf(
                '$entry[%1$s] = isset($entry[%1$s]) && is_scalar($entry[%1$s]) ? (string) $entry[%1$s] : \'\';',
                $altHandleLiteral,
            );
        }
        $prepareEditLines[] = sprintf('$imageData = $this->%s($entry[%s] ?? []);', self::NORMALIZE_DATA_METHOD, $dataHandleLiteral);
        foreach ($this->getImageDataKeys($field) as $imageDataKey) {
            $prepareEditLines[] = sprintf(
                '$entry[%1$s] = $imageData[%2$s];',
                $this->phpLiteralFormatter->format($field->handle . '_' . $imageDataKey),
                $this->phpLiteralFormatter->format($imageDataKey),
            );
        }
        $prepareEditLines[] = sprintf(
            '$entry[%1$s] = json_encode($imageData, JSON_THROW_ON_ERROR);',
            $dataHandleLiteral,
        );

        $validationCode = $this->renderFileValidation(
            $field,
            '$entry[' . $handleLiteral . '] ?? null',
            repeatable: true,
        );
        if ($field->thumbnailEditable || $field->fullscreenEditable) {
            $validationCode .= PHP_EOL . PHP_EOL . $this->renderInlineDimensionsValidationCode(
                $field,
                '$entry',
                repeatable: true,
            );
        }

        $planBuilder->controller
            ->addMethodFragment(ControllerMethodSectionEnum::View->value, new CodeFragment(
                key: $fragmentKeyPrefix . '.defaults',
                code: $this->renderRepeatableDefaultViewVariables($field),
                order: $context->position,
            ))
            ->addMethodFragment(ControllerMethodSectionEnum::SaveEntryFields->value, new CodeFragment(
                key: $fragmentKeyPrefix,
                code: implode(PHP_EOL, $saveLines),
                order: $context->position,
            ))
            ->addMethodFragment(ControllerMethodSectionEnum::ValidateEntryFields->value, new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $validationCode,
                order: $context->position,
            ))
            ->addMethodFragment(ControllerMethodSectionEnum::PrepareEntryForEdit->value, new CodeFragment(
                key: $fragmentKeyPrefix,
                code: implode(PHP_EOL, $prepareEditLines),
                order: $context->position,
            ))
            ->addMethodFragment(ControllerMethodSectionEnum::PrepareEntryForView->value, new CodeFragment(
                key: $fragmentKeyPrefix,
                code: $this->renderRepeatableViewPreparation($field),
                order: $context->position,
            ));
    }

    private function contributeEditableSettings(
        FieldGenerationContext $context,
        ImageFieldTypeDto $field,
        string $fragmentKeyPrefix,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $planBuilder->controller
            ->addMethodFragment(ControllerMethodSectionEnum::AddEdit->value, new CodeFragment(
                key: 'image.settings',
                code: sprintf(
                    '$settings = $this->%1$s($this->settings ?? []);%2$s$this->set(\'settings\', $settings);',
                    self::DECODE_DATA_METHOD,
                    PHP_EOL,
                ),
                order: -100,
            ))
            ->addMethodFragment(ControllerMethodSectionEnum::SaveBasicFields->value, new CodeFragment(
                key: $fragmentKeyPrefix . '.settings',
                code: $this->renderSettingsSaveCode($field),
                order: $context->position,
            ))
            ->addMethodFragment(ControllerMethodSectionEnum::ValidateBasicFields->value, new CodeFragment(
                key: $fragmentKeyPrefix . '.settings',
                code: $this->renderSettingsValidationCode($field),
                order: $context->position,
            ));

        $planBuilder->form->addSettingsFragment(new CodeFragment(
            key: $fragmentKeyPrefix,
            code: $this->renderSettingsFragment($context, $field),
            order: $context->position,
        ));
    }

    private function renderSettingsSaveCode(ImageFieldTypeDto $field): string
    {
        $lines = [
            'if (!isset($blockBuilderSubmittedImageSettings, $blockBuilderNormalizedImageSettings)) {',
            '    $blockBuilderSubmittedImageSettings = is_array($args[\'settings\'] ?? null) ? $args[\'settings\'] : [];',
            '    $blockBuilderNormalizedImageSettings = [];',
            '}',
        ];
        foreach ($this->getEditableSettingDefinitions($field) as $definition) {
            $keyLiteral = $this->phpLiteralFormatter->format($field->handle . '_' . $definition['suffix']);
            $lines[] = match ($definition['kind']) {
                'boolean' => sprintf(
                    '$blockBuilderNormalizedImageSettings[%1$s] = !empty($blockBuilderSubmittedImageSettings[%1$s]) ? 1 : 0;',
                    $keyLiteral,
                ),
                'integer' => sprintf(
                    '$blockBuilderNormalizedImageSettings[%1$s] = isset($blockBuilderSubmittedImageSettings[%1$s]) && is_numeric($blockBuilderSubmittedImageSettings[%1$s]) ? max(0, (int) $blockBuilderSubmittedImageSettings[%1$s]) : 0;',
                    $keyLiteral,
                ),
                default => '',
            };
        }
        $lines[] = '$args[\'settings\'] = json_encode($blockBuilderNormalizedImageSettings, JSON_THROW_ON_ERROR);';

        return implode(PHP_EOL, $lines);
    }

    private function renderSettingsValidationCode(ImageFieldTypeDto $field): string
    {
        $settingsExpression = '(is_array($args[\'settings\'] ?? null) ? $args[\'settings\'] : [])';
        $lines = ['$submittedImageSettings = ' . $settingsExpression . ';'];
        foreach ([
            ['enabled' => $field->thumbnailEditable, 'override' => 'override_dimensions', 'width' => 'custom_width', 'height' => 'custom_height', 'crop' => 'custom_crop', 'label' => 'thumbnail'],
            ['enabled' => $field->fullscreenEditable, 'override' => 'override_fullscreen_dimensions', 'width' => 'custom_fullscreen_width', 'height' => 'custom_fullscreen_height', 'crop' => 'custom_fullscreen_crop', 'label' => 'fullscreen image'],
        ] as $variant) {
            if (!$variant['enabled']) {
                continue;
            }
            $override = $this->phpLiteralFormatter->format($field->handle . '_' . $variant['override']);
            $width = $this->phpLiteralFormatter->format($field->handle . '_' . $variant['width']);
            $height = $this->phpLiteralFormatter->format($field->handle . '_' . $variant['height']);
            $crop = $this->phpLiteralFormatter->format($field->handle . '_' . $variant['crop']);
            $label = $this->phpLiteralFormatter->format($field->label);
            $variantLabel = $this->phpLiteralFormatter->format($variant['label']);
            $lines[] = sprintf(
                'if (!empty($submittedImageSettings[%1$s])) {%2$s'
                . '    $customWidth = $submittedImageSettings[%3$s] ?? null;%2$s'
                . '    $customHeight = $submittedImageSettings[%4$s] ?? null;%2$s'
                . '    if (($customWidth !== null && $customWidth !== \'\' && (!is_numeric($customWidth) || (int) $customWidth < 1)) || ($customHeight !== null && $customHeight !== \'\' && (!is_numeric($customHeight) || (int) $customHeight < 1))) {%2$s'
                . '        $errors->add(t(\'The custom %%s dimensions for the field "%%s" must be positive whole numbers.\', t(%5$s), t(%6$s)));%2$s'
                . '    } elseif (empty($customWidth) && empty($customHeight)) {%2$s'
                . '        $errors->add(t(\'Enter a custom width, height, or both for the %%s in the field "%%s".\', t(%5$s), t(%6$s)));%2$s'
                . '    } elseif (!empty($submittedImageSettings[%7$s]) && (empty($customWidth) || empty($customHeight))) {%2$s'
                . '        $errors->add(t(\'Both custom dimensions are required when cropping the %%s in the field "%%s".\', t(%5$s), t(%6$s)));%2$s'
                . '    }%2$s'
                . '}',
                $override,
                PHP_EOL,
                $width,
                $height,
                $variantLabel,
                $label,
                $crop,
            );
        }

        return implode(PHP_EOL, $lines);
    }

    private function renderSubmittedImageDataArray(ImageFieldTypeDto $field, string $sourceExpression): string
    {
        $lines = ['['];
        foreach ($this->getImageDataKeys($field) as $imageDataKey) {
            $submittedKey = $this->phpLiteralFormatter->format($field->handle . '_' . $imageDataKey);
            $dataKey = $this->phpLiteralFormatter->format($imageDataKey);
            $integerValue = in_array($imageDataKey, [
                'custom_width',
                'custom_height',
                'custom_fullscreen_width',
                'custom_fullscreen_height',
            ], true);
            $value = $integerValue
                ? sprintf(
                    'isset(%1$s[%2$s]) && is_numeric(%1$s[%2$s]) ? max(0, (int) %1$s[%2$s]) : 0',
                    $sourceExpression,
                    $submittedKey,
                )
                : sprintf('!empty(%s[%s]) ? 1 : 0', $sourceExpression, $submittedKey);
            $lines[] = sprintf('    %s => %s,', $dataKey, $value);
        }
        $lines[] = ']';

        return implode(PHP_EOL, $lines);
    }

    private function renderInlineDimensionsValidationCode(
        ImageFieldTypeDto $field,
        string $sourceExpression,
        bool $repeatable = false,
    ): string {
        $lines = [];
        foreach ([
            ['enabled' => $field->thumbnailEditable, 'override' => 'override_dimensions', 'width' => 'custom_width', 'height' => 'custom_height', 'crop' => 'custom_crop', 'label' => 'thumbnail'],
            ['enabled' => $field->fullscreenEditable, 'override' => 'override_fullscreen_dimensions', 'width' => 'custom_fullscreen_width', 'height' => 'custom_fullscreen_height', 'crop' => 'custom_fullscreen_crop', 'label' => 'fullscreen image'],
        ] as $variant) {
            if (!$variant['enabled']) {
                continue;
            }
            $override = $this->phpLiteralFormatter->format($field->handle . '_' . $variant['override']);
            $width = $this->phpLiteralFormatter->format($field->handle . '_' . $variant['width']);
            $height = $this->phpLiteralFormatter->format($field->handle . '_' . $variant['height']);
            $crop = $this->phpLiteralFormatter->format($field->handle . '_' . $variant['crop']);
            $fieldLabel = $this->phpLiteralFormatter->format($field->label);
            $variantLabel = $this->phpLiteralFormatter->format($variant['label']);
            $entrySuffix = $repeatable ? ' in entry %s' : '';
            $entryArgument = $repeatable ? ', $entryPosition + 1' : '';
            $lines[] = sprintf(
                'if (!empty(%1$s[%2$s])) {%3$s'
                . '    $customWidth = %1$s[%4$s] ?? null;%3$s'
                . '    $customHeight = %1$s[%5$s] ?? null;%3$s'
                . '    if (($customWidth !== null && $customWidth !== \'\' && (!is_numeric($customWidth) || (int) $customWidth < 1)) || ($customHeight !== null && $customHeight !== \'\' && (!is_numeric($customHeight) || (int) $customHeight < 1))) {%3$s'
                . '        $errors->add(t(\'The custom %%s dimensions for the field "%%s"%6$s must be positive whole numbers.\', t(%7$s), t(%8$s)%9$s));%3$s'
                . '    } elseif (empty($customWidth) && empty($customHeight)) {%3$s'
                . '        $errors->add(t(\'Enter a custom width, height, or both for the %%s in the field "%%s"%6$s.\', t(%7$s), t(%8$s)%9$s));%3$s'
                . '    } elseif (!empty(%1$s[%10$s]) && (empty($customWidth) || empty($customHeight))) {%3$s'
                . '        $errors->add(t(\'Both custom dimensions are required when cropping the %%s in the field "%%s"%6$s.\', t(%7$s), t(%8$s)%9$s));%3$s'
                . '    }%3$s'
                . '}',
                $sourceExpression,
                $override,
                PHP_EOL,
                $width,
                $height,
                $entrySuffix,
                $variantLabel,
                $fieldLabel,
                $entryArgument,
                $crop,
            );
        }

        return implode(PHP_EOL . PHP_EOL, $lines);
    }

    private function renderFileValidation(ImageFieldTypeDto $field, string $valueExpression, bool $repeatable = false): string
    {
        $label = sprintf('t(%s)', $this->phpLiteralFormatter->format($field->label));
        if ($repeatable) {
            if (!$field->required) {
                return sprintf(
                    '$imageFileID = %1$s;%2$sif ((int) $imageFileID > 0 && !$this->%3$s($imageFileID)) {%2$s    $errors->add(t(\'The field "%%s" must reference a valid image in entry %%s.\', %4$s, $entryPosition + 1));%2$s}',
                    $valueExpression,
                    PHP_EOL,
                    self::VALIDATE_FILE_METHOD,
                    $label,
                );
            }

            return sprintf(
                '$imageFileID = %1$s;%2$sif ((int) $imageFileID < 1) {%2$s    $errors->add(t(\'The field "%%s" is required in entry %%s.\', %3$s, $entryPosition + 1));%2$s} elseif (!$this->%4$s($imageFileID)) {%2$s    $errors->add(t(\'The field "%%s" must reference a valid image in entry %%s.\', %3$s, $entryPosition + 1));%2$s}',
                $valueExpression,
                PHP_EOL,
                $label,
                self::VALIDATE_FILE_METHOD,
            );
        }

        if (!$field->required) {
            return sprintf(
                '$imageFileID = %1$s;%2$sif ((int) $imageFileID > 0 && !$this->%3$s($imageFileID)) {%2$s    $errors->add(t(\'The field "%%s" must reference a valid image.\', %4$s));%2$s}',
                $valueExpression,
                PHP_EOL,
                self::VALIDATE_FILE_METHOD,
                $label,
            );
        }

        return sprintf(
            '$imageFileID = %1$s;%2$sif ((int) $imageFileID < 1) {%2$s    $errors->add(t(\'The field "%%s" is required.\', %3$s));%2$s} elseif (!$this->%4$s($imageFileID)) {%2$s    $errors->add(t(\'The field "%%s" must reference a valid image.\', %3$s));%2$s}',
            $valueExpression,
            PHP_EOL,
            $label,
            self::VALIDATE_FILE_METHOD,
        );
    }

    private function renderBasicViewPreparation(ImageFieldTypeDto $field): string
    {
        $handle = $field->handle;
        $options = $this->renderOptionsCode($field, '$this->' . $handle . '_data', false);
        $lines = [
            $options,
            sprintf(
                '$resolvedImage = $this->%1$s($this->%2$s ?? 0, %3$s, $imageOptions);',
                self::RESOLVE_IMAGE_METHOD,
                $handle,
                $field->showAltTextField ? '$this->' . $handle . '_alt ?? \'\'' : '\'\'',
            ),
        ];
        foreach ($this->getResolvedVariableSuffixes(includeObject: true) as $suffix => $resolvedKey) {
            $lines[] = sprintf(
                '$this->set(%s, $resolvedImage[%s]);',
                $this->phpLiteralFormatter->format($handle . $suffix),
                $this->phpLiteralFormatter->format($resolvedKey),
            );
        }
        $lines[] = sprintf(
            '$this->set(%s, %s);',
            $this->phpLiteralFormatter->format($handle . '_defaultThumbnailWidth'),
            $field->thumbnailWidth ?? 0,
        );
        $lines[] = sprintf(
            '$this->set(%s, %s);',
            $this->phpLiteralFormatter->format($handle . '_defaultThumbnailHeight'),
            $field->thumbnailHeight ?? 0,
        );
        $lines[] = sprintf(
            '$this->set(%s, %s);',
            $this->phpLiteralFormatter->format($handle . '_defaultFullscreenWidth'),
            $field->fullscreenWidth ?? 0,
        );
        $lines[] = sprintf(
            '$this->set(%s, %s);',
            $this->phpLiteralFormatter->format($handle . '_defaultFullscreenHeight'),
            $field->fullscreenHeight ?? 0,
        );

        return implode(PHP_EOL, $lines);
    }

    private function renderRepeatableViewPreparation(ImageFieldTypeDto $field): string
    {
        $handleLiteral = $this->phpLiteralFormatter->format($field->handle);
        $altHandleLiteral = $this->phpLiteralFormatter->format($field->handle . '_alt');
        $dataHandleLiteral = $this->phpLiteralFormatter->format($field->handle . '_data');
        $lines = [
            $this->renderOptionsCode($field, '$entry[' . $dataHandleLiteral . '] ?? []', true),
            sprintf(
                '$resolvedImage = $this->%1$s($entry[%2$s] ?? 0, %3$s, $imageOptions);',
                self::RESOLVE_IMAGE_METHOD,
                $handleLiteral,
                $field->showAltTextField ? '$entry[' . $altHandleLiteral . '] ?? \'\'' : '\'\'',
            ),
        ];
        foreach ($this->getResolvedVariableSuffixes(includeObject: false) as $suffix => $resolvedKey) {
            $lines[] = sprintf(
                '$entry[%s] = $resolvedImage[%s];',
                $this->phpLiteralFormatter->format($field->handle . $suffix),
                $this->phpLiteralFormatter->format($resolvedKey),
            );
        }

        return implode(PHP_EOL, $lines);
    }

    private function renderOptionsCode(
        ImageFieldTypeDto $field,
        string $imageDataExpression,
        bool $repeatable,
    ): string
    {
        $handle = $field->handle;
        $lines = [
            sprintf('$imageData = $this->%s(%s);', self::NORMALIZE_DATA_METHOD, $imageDataExpression),
            '$thumbnailWidth = ' . ($field->thumbnailWidth ?? 0) . ';',
            '$thumbnailHeight = ' . ($field->thumbnailHeight ?? 0) . ';',
            '$thumbnailCrop = ' . ($field->thumbnailCrop ? 'true' : 'false') . ';',
            '$fullscreenWidth = ' . ($field->fullscreenWidth ?? 0) . ';',
            '$fullscreenHeight = ' . ($field->fullscreenHeight ?? 0) . ';',
            '$fullscreenCrop = ' . ($field->fullscreenCrop ? 'true' : 'false') . ';',
        ];
        if ($repeatable) {
            $prefix = $this->phpLiteralFormatter->format($handle . '_');
            $lines[] = sprintf('$imageSettings = $this->%s($this->settings ?? []);', self::DECODE_DATA_METHOD);
            $lines[] = sprintf(
                'if (!empty($imageSettings[%1$s . \'override_dimensions\'])) {%2$s'
                . '    $thumbnailWidth = $imageSettings[%1$s . \'custom_width\'] ?? 0;%2$s'
                . '    $thumbnailHeight = $imageSettings[%1$s . \'custom_height\'] ?? 0;%2$s'
                . '    $thumbnailCrop = !empty($imageSettings[%1$s . \'custom_crop\']);%2$s'
                . '}',
                $prefix,
                PHP_EOL,
            );
            $lines[] = sprintf(
                'if (!empty($imageSettings[%1$s . \'override_fullscreen_dimensions\'])) {%2$s'
                . '    $fullscreenWidth = $imageSettings[%1$s . \'custom_fullscreen_width\'] ?? 0;%2$s'
                . '    $fullscreenHeight = $imageSettings[%1$s . \'custom_fullscreen_height\'] ?? 0;%2$s'
                . '    $fullscreenCrop = !empty($imageSettings[%1$s . \'custom_fullscreen_crop\']);%2$s'
                . '}',
                $prefix,
                PHP_EOL,
            );
        }
        $lines[] = 'if (!empty($imageData[\'override_dimensions\'])) {';
        $lines[] = '    $thumbnailWidth = $imageData[\'custom_width\'];';
        $lines[] = '    $thumbnailHeight = $imageData[\'custom_height\'];';
        $lines[] = '    $thumbnailCrop = !empty($imageData[\'custom_crop\']);';
        $lines[] = '}';
        $lines[] = 'if (!empty($imageData[\'override_fullscreen_dimensions\'])) {';
        $lines[] = '    $fullscreenWidth = $imageData[\'custom_fullscreen_width\'];';
        $lines[] = '    $fullscreenHeight = $imageData[\'custom_fullscreen_height\'];';
        $lines[] = '    $fullscreenCrop = !empty($imageData[\'custom_fullscreen_crop\']);';
        $lines[] = '}';
        $lines[] = '$imageOptions = [';
        $lines[] = '    \'thumbnail\' => ' . ($field->createThumbnailImage ? 'true' : 'false') . ',';
        $lines[] = '    \'thumbnailWidth\' => $thumbnailWidth,';
        $lines[] = '    \'thumbnailHeight\' => $thumbnailHeight,';
        $lines[] = '    \'thumbnailCrop\' => $thumbnailCrop,';
        $lines[] = '    \'fullscreen\' => ' . ($field->createFullscreenImage ? 'true' : 'false') . ',';
        $lines[] = '    \'fullscreenWidth\' => $fullscreenWidth,';
        $lines[] = '    \'fullscreenHeight\' => $fullscreenHeight,';
        $lines[] = '    \'fullscreenCrop\' => $fullscreenCrop,';
        $lines[] = '];';

        return implode(PHP_EOL, $lines);
    }

    private function contributeViewDocumentation(
        FieldGenerationContext $context,
        ImageFieldTypeDto $field,
        BlockGenerationPlanBuilder $planBuilder,
    ): void {
        $variables = [
            '' => ['int', $field->label . ' file ID'],
            '_alt' => ['string', 'Alternative text for ' . $field->label],
            '_filename' => ['string', 'Filename for ' . $field->label],
            '_type' => ['string', 'File type for ' . $field->label],
            '_relativePath' => ['string', 'Relative file path for ' . $field->label],
            '_link' => ['string', 'Original image URL for ' . $field->label],
            '_width' => ['int', 'Original image width for ' . $field->label],
            '_height' => ['int', 'Original image height for ' . $field->label],
            '_fullscreenLink' => ['string', 'Fullscreen image URL for ' . $field->label],
            '_fullscreenWidth' => ['int', 'Fullscreen image width for ' . $field->label],
            '_fullscreenHeight' => ['int', 'Fullscreen image height for ' . $field->label],
            '_thumbnailLink' => ['string', 'Thumbnail URL for ' . $field->label],
            '_thumbnailWidth' => ['int', 'Thumbnail width for ' . $field->label],
            '_thumbnailHeight' => ['int', 'Thumbnail height for ' . $field->label],
        ];
        if ($context->isBasicField()) {
            $variables = ['_object' => ['\Concrete\Core\Entity\File\File|false', 'File object for ' . $field->label], ...$variables];
        }
        $offset = 0;
        foreach ($variables as $suffix => [$type, $description]) {
            $planBuilder->view->addFieldVariable(
                $context->fieldContext,
                new ViewVariableDocumentation(
                    name: $field->handle . $suffix,
                    type: $type,
                    description: $description,
                    order: ($context->position * 20) + $offset++,
                ),
            );
        }

        $defaultVariables = $context->isBasicField()
            ? [
                '_defaultThumbnailWidth' => ['int', 'Default thumbnail width for ' . $field->label],
                '_defaultThumbnailHeight' => ['int', 'Default thumbnail height for ' . $field->label],
                '_defaultFullscreenWidth' => ['int', 'Default fullscreen width for ' . $field->label],
                '_defaultFullscreenHeight' => ['int', 'Default fullscreen height for ' . $field->label],
            ]
            : [
                '_defaultRepeatableThumbnailWidth' => ['int', 'Default repeatable thumbnail width for ' . $field->label],
                '_defaultRepeatableThumbnailHeight' => ['int', 'Default repeatable thumbnail height for ' . $field->label],
                '_defaultRepeatableFullscreenWidth' => ['int', 'Default repeatable fullscreen width for ' . $field->label],
                '_defaultRepeatableFullscreenHeight' => ['int', 'Default repeatable fullscreen height for ' . $field->label],
            ];
        foreach ($defaultVariables as $suffix => [$type, $description]) {
            $planBuilder->view->addVariable(new ViewVariableDocumentation(
                name: $field->handle . $suffix,
                type: $type,
                description: $description,
                order: ($context->position * 20) + $offset++,
            ));
        }
    }

    private function renderRepeatableDefaultViewVariables(ImageFieldTypeDto $field): string
    {
        $defaults = [
            '_defaultRepeatableThumbnailWidth' => $field->thumbnailWidth ?? 0,
            '_defaultRepeatableThumbnailHeight' => $field->thumbnailHeight ?? 0,
            '_defaultRepeatableFullscreenWidth' => $field->fullscreenWidth ?? 0,
            '_defaultRepeatableFullscreenHeight' => $field->fullscreenHeight ?? 0,
        ];
        $lines = [];
        foreach ($defaults as $suffix => $value) {
            $lines[] = sprintf(
                '$this->set(%s, %d);',
                $this->phpLiteralFormatter->format($field->handle . $suffix),
                $value,
            );
        }

        return implode(PHP_EOL, $lines);
    }

    private function renderFormFragment(FieldGenerationContext $context): string
    {
        /** @var ImageFieldTypeDto $field */
        $field = $context->fieldDto;
        $basicField = $context->isBasicField();
        $helpText = $field->helpText !== null && $field->helpText !== ''
            ? PHP_EOL . '    <div class="form-text"><?= t(' . $this->phpLiteralFormatter->format($field->helpText) . '); ?></div>'
            : '';
        $additionalFields = [];
        if ($field->showAltTextField) {
            $altName = $basicField
                ? '<?= h($view->field(' . $this->phpLiteralFormatter->format($field->handle . '_alt') . ')); ?>'
                : '<?= h($view->field(\'entry\')); ?>[<?= h((string) $entryIndex); ?>][' . $field->handle . '_alt]';
            $altValue = $basicField
                ? '$' . $field->handle . '_alt ?? \'\''
                : '$entry[' . $this->phpLiteralFormatter->format($field->handle . '_alt') . '] ?? \'\'';
            $additionalFields[] = sprintf(
                '<div class="mb-4">%1$s    <label class="form-label" for="%2$s"><?= t(%3$s); ?></label>%1$s    <input class="form-control" id="%2$s" maxlength="255" name="%2$s" type="text" value="<?= h(%4$s); ?>"%5$s>%1$s</div>',
                PHP_EOL,
                $altName,
                $this->phpLiteralFormatter->format($context->config->altTextLabel ?: 'Alt text'),
                $altValue,
                $basicField ? '' : ' data-entry-field="' . $field->handle . '_alt"',
            );
        }
        if ($field->thumbnailEditable) {
            $additionalFields[] = $this->renderInlineDimensionsFragment($context, $field, false);
        }
        if ($field->fullscreenEditable) {
            $additionalFields[] = $this->renderInlineDimensionsFragment($context, $field, true);
        }
        $highlightField = $context->config->highlightMultiElementFields && $additionalFields !== [];

        $additionalFieldsMarkup = '';
        if ($additionalFields !== []) {
            $stateKey = $field->handle . '_show_additional_fields';
            $stateName = $basicField
                ? '<?= h($view->field(' . $this->phpLiteralFormatter->format($stateKey) . ')); ?>'
                : '<?= h($view->field(\'entry\')); ?>[<?= h((string) $entryIndex); ?>][' . $stateKey . ']';
            $stateExpression = $basicField
                ? '$' . $stateKey . ' ?? 0'
                : '$entry[' . $this->phpLiteralFormatter->format($stateKey) . '] ?? 0';
            $additionalFieldsMarkup = PHP_EOL . $this->indentGeneratedMarkup($this->stubRenderer->render(
                'fragments/image/additional-fields.php.stub',
                [
                    '{{SHOW_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format(
                        $context->config->showAdditionalFieldsLabel ?: 'Show additional fields',
                    ),
                    '{{HIDE_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format(
                        $context->config->hideAdditionalFieldsLabel ?: 'Hide additional fields',
                    ),
                    '{{STATE_EXPRESSION}}' => $stateExpression,
                    '{{STATE_NAME}}' => $stateName,
                    '{{STATE_ENTRY_FIELD}}' => $basicField ? '' : ' data-entry-field="' . $stateKey . '"',
                    '{{FIELDS}}' => PHP_EOL . $this->indentGeneratedMarkup(
                        implode(PHP_EOL . PHP_EOL, $additionalFields),
                    ),
                ],
            ), 2);
        }

        return $this->stubRenderer->render(
            'fragments/image/form-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            [
                '{{HIGHLIGHT_CLASS}}' => $highlightField ? ' field-group-highlight' : '',
                '{{HANDLE}}' => $field->handle,
                '{{HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($field->handle),
                '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
                '{{REQUIRED_LABEL_SUFFIX}}' => $field->required ? ' . \' *\'' : '',
                '{{HELP_TEXT}}' => $helpText,
                '{{ADDITIONAL_FIELDS}}' => $additionalFieldsMarkup,
            ],
        );
    }

    private function renderInlineDimensionsFragment(
        FieldGenerationContext $context,
        ImageFieldTypeDto $field,
        bool $fullscreen,
    ): string {
        $suffixes = $fullscreen
            ? ['override_fullscreen_dimensions', 'custom_fullscreen_width', 'custom_fullscreen_height', 'custom_fullscreen_crop']
            : ['override_dimensions', 'custom_width', 'custom_height', 'custom_crop'];
        [$overrideSuffix, $widthSuffix, $heightSuffix, $cropSuffix] = $suffixes;
        $basicField = $context->isBasicField();
        $name = function (string $suffix) use ($basicField, $field): string {
            $handle = $field->handle . '_' . $suffix;

            return $basicField
                ? '<?= h($view->field(' . $this->phpLiteralFormatter->format($handle) . ')); ?>'
                : '<?= h($view->field(\'entry\')); ?>[<?= h((string) $entryIndex); ?>][' . $handle . ']';
        };
        $value = function (string $suffix) use ($basicField, $field): string {
            $handle = $field->handle . '_' . $suffix;

            return $basicField
                ? '$' . $handle . ' ?? 0'
                : '$entry[' . $this->phpLiteralFormatter->format($handle) . '] ?? 0';
        };
        $entryField = static fn(string $suffix): string => $basicField
            ? ''
            : ' data-entry-field="' . $field->handle . '_' . $suffix . '"';

        return $this->stubRenderer->render('fragments/image/field-dimensions.php.stub', [
            '{{OVERRIDE_NAME}}' => $name($overrideSuffix),
            '{{WIDTH_NAME}}' => $name($widthSuffix),
            '{{HEIGHT_NAME}}' => $name($heightSuffix),
            '{{CROP_NAME}}' => $name($cropSuffix),
            '{{OVERRIDE_VALUE_EXPRESSION}}' => $value($overrideSuffix),
            '{{WIDTH_VALUE_EXPRESSION}}' => $value($widthSuffix),
            '{{HEIGHT_VALUE_EXPRESSION}}' => $value($heightSuffix),
            '{{CROP_VALUE_EXPRESSION}}' => $value($cropSuffix),
            '{{OVERRIDE_ENTRY_FIELD}}' => $entryField($overrideSuffix),
            '{{WIDTH_ENTRY_FIELD}}' => $entryField($widthSuffix),
            '{{HEIGHT_ENTRY_FIELD}}' => $entryField($heightSuffix),
            '{{CROP_ENTRY_FIELD}}' => $entryField($cropSuffix),
            '{{OVERRIDE_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format(
                $fullscreen
                    ? ($context->config->overrideFullscreenImageDimensionsLabel ?: 'Override fullscreen image dimensions')
                    : ($context->config->overrideThumbnailDimensionsLabel ?: 'Override thumbnail dimensions'),
            ),
            '{{WIDTH_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->widthLabel ?: 'Width'),
            '{{HEIGHT_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->heightLabel ?: 'Height'),
            '{{CROP_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->cropLabel ?: 'Crop'),
            '{{PX_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->pxLabel ?: 'px'),
            '{{YES_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->yesLabel ?: 'Yes'),
            '{{NO_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->noLabel ?: 'No'),
        ]);
    }

    private function renderSettingsFragment(FieldGenerationContext $context, ImageFieldTypeDto $field): string
    {
        $thumbnailSettings = $field->thumbnailEditable
            ? $this->renderDimensionsFragment($context, $field, false)
            : '';
        $fullscreenSettings = $field->fullscreenEditable
            ? $this->renderDimensionsFragment($context, $field, true)
            : '';

        return $this->stubRenderer->render('fragments/image/settings.php.stub', [
            '{{LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($field->label),
            '{{THUMBNAIL_SETTINGS}}' => $thumbnailSettings === ''
                ? ''
                : PHP_EOL . $this->indentGeneratedMarkup($thumbnailSettings),
            '{{FULLSCREEN_SETTINGS}}' => $fullscreenSettings === ''
                ? ''
                : PHP_EOL . $this->indentGeneratedMarkup($fullscreenSettings),
        ]);
    }

    private function indentGeneratedMarkup(string $markup, int $levels = 1): string
    {
        $indentation = str_repeat('    ', $levels);

        return implode(
            PHP_EOL,
            array_map(
                static fn(string $line): string => trim($line) === '' ? '' : $indentation . $line,
                explode(PHP_EOL, $markup),
            ),
        );
    }

    private function renderDimensionsFragment(
        FieldGenerationContext $context,
        ImageFieldTypeDto $field,
        bool $fullscreen,
    ): string
    {
        $suffixes = $fullscreen
            ? ['override_fullscreen_dimensions', 'custom_fullscreen_width', 'custom_fullscreen_height', 'custom_fullscreen_crop']
            : ['override_dimensions', 'custom_width', 'custom_height', 'custom_crop'];
        [$overrideSuffix, $widthSuffix, $heightSuffix, $cropSuffix] = $suffixes;
        $key = static fn(string $suffix): string => $field->handle . '_' . $suffix;
        $input = static fn(string $suffix): string => 'settings[' . $field->handle . '_' . $suffix . ']';

        return $this->stubRenderer->render('fragments/image/dimensions.php.stub', [
            '{{OVERRIDE_HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($input($overrideSuffix)),
            '{{WIDTH_HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($input($widthSuffix)),
            '{{HEIGHT_HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($input($heightSuffix)),
            '{{CROP_HANDLE_LITERAL}}' => $this->phpLiteralFormatter->format($input($cropSuffix)),
            '{{OVERRIDE_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($key($overrideSuffix)),
            '{{WIDTH_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($key($widthSuffix)),
            '{{HEIGHT_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($key($heightSuffix)),
            '{{CROP_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($key($cropSuffix)),
            '{{OVERRIDE_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format(
                $fullscreen
                    ? ($context->config->overrideFullscreenImageDimensionsLabel ?: 'Override fullscreen image dimensions')
                    : ($context->config->overrideThumbnailDimensionsLabel ?: 'Override thumbnail dimensions'),
            ),
            '{{WIDTH_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->widthLabel ?: 'Width'),
            '{{HEIGHT_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->heightLabel ?: 'Height'),
            '{{CROP_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->cropLabel ?: 'Crop'),
            '{{PX_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->pxLabel ?: 'px'),
            '{{YES_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->yesLabel ?: 'Yes'),
            '{{NO_LABEL_LITERAL}}' => $this->phpLiteralFormatter->format($context->config->noLabel ?: 'No'),
        ]);
    }

    private function renderViewFragment(ImageFieldTypeDto $field, bool $basicField): string
    {
        $handle = $field->handle;
        $fullscreenImage = $field->createFullscreenImage
            ? ($basicField
                ? sprintf(
                    '<?php if (!empty($%1$s_fullscreenLink)): ?>%2$s'
                    . '    <img src="<?= h($%1$s_fullscreenLink); ?>"%2$s'
                    . '         alt="<?= h($%1$s_alt ?? \'\'); ?>"%2$s'
                    . '         width="<?= h($%1$s_fullscreenWidth ?? 0); ?>"%2$s'
                    . '         height="<?= h($%1$s_fullscreenHeight ?? 0); ?>"%2$s'
                    . '    >%2$s'
                    . '<?php endif; ?>',
                    $handle,
                    PHP_EOL,
                )
                : sprintf(
                    '<?php if (!empty($entry[%1$s])): ?>%2$s'
                    . '    <img src="<?= h($entry[%1$s]); ?>"%2$s'
                    . '         alt="<?= h($entry[%3$s] ?? \'\'); ?>"%2$s'
                    . '         width="<?= h($entry[%4$s] ?? 0); ?>"%2$s'
                    . '         height="<?= h($entry[%5$s] ?? 0); ?>"%2$s'
                    . '    >%2$s'
                    . '<?php endif; ?>',
                    $this->phpLiteralFormatter->format($handle . '_fullscreenLink'),
                    PHP_EOL,
                    $this->phpLiteralFormatter->format($handle . '_alt'),
                    $this->phpLiteralFormatter->format($handle . '_fullscreenWidth'),
                    $this->phpLiteralFormatter->format($handle . '_fullscreenHeight'),
                ))
            : '';
        $thumbnailImage = $field->createThumbnailImage
            ? ($basicField
                ? sprintf(
                    '<?php if (!empty($%1$s_thumbnailLink)): ?>%2$s'
                    . '    <img src="<?= h($%1$s_thumbnailLink); ?>"%2$s'
                    . '         alt="<?= h($%1$s_alt ?? \'\'); ?>"%2$s'
                    . '         width="<?= h($%1$s_thumbnailWidth ?? 0); ?>"%2$s'
                    . '         height="<?= h($%1$s_thumbnailHeight ?? 0); ?>"%2$s'
                    . '    >%2$s'
                    . '<?php endif; ?>',
                    $handle,
                    PHP_EOL,
                )
                : sprintf(
                    '<?php if (!empty($entry[%1$s])): ?>%2$s'
                    . '    <img src="<?= h($entry[%1$s]); ?>"%2$s'
                    . '         alt="<?= h($entry[%3$s] ?? \'\'); ?>"%2$s'
                    . '         width="<?= h($entry[%4$s] ?? 0); ?>"%2$s'
                    . '         height="<?= h($entry[%5$s] ?? 0); ?>"%2$s'
                    . '    >%2$s'
                    . '<?php endif; ?>',
                    $this->phpLiteralFormatter->format($handle . '_thumbnailLink'),
                    PHP_EOL,
                    $this->phpLiteralFormatter->format($handle . '_alt'),
                    $this->phpLiteralFormatter->format($handle . '_thumbnailWidth'),
                    $this->phpLiteralFormatter->format($handle . '_thumbnailHeight'),
                ))
            : '';

        $replacements = [];
        if ($basicField) {
            $replacements['{{HANDLE}}'] = $handle;
        } else {
            $replacements += [
                '{{LINK_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($handle . '_link'),
                '{{ALT_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($handle . '_alt'),
                '{{WIDTH_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($handle . '_width'),
                '{{HEIGHT_KEY_LITERAL}}' => $this->phpLiteralFormatter->format($handle . '_height'),
            ];
        }

        $originalImage = $this->stubRenderer->render(
            'fragments/image/view-' . ($basicField ? 'basic' : 'repeatable') . '.php.stub',
            $replacements,
        );

        return implode(
            PHP_EOL . PHP_EOL,
            array_filter(
                [$thumbnailImage, $fullscreenImage, $originalImage],
                static fn(string $image): bool => $image !== '',
            ),
        );
    }

    /**
     * @return list<string>
     */
    private function getImageDataKeys(ImageFieldTypeDto $field): array
    {
        $keys = ['show_additional_fields'];
        if ($field->thumbnailEditable) {
            array_push($keys, 'override_dimensions', 'custom_width', 'custom_height', 'custom_crop');
        }
        if ($field->fullscreenEditable) {
            array_push(
                $keys,
                'override_fullscreen_dimensions',
                'custom_fullscreen_width',
                'custom_fullscreen_height',
                'custom_fullscreen_crop',
            );
        }

        return $keys;
    }

    /**
     * @return list<array{suffix: string, kind: string}>
     */
    private function getEditableSettingDefinitions(ImageFieldTypeDto $field): array
    {
        $definitions = [];
        if ($field->thumbnailEditable) {
            $definitions = [
                ['suffix' => 'override_dimensions', 'kind' => 'boolean'],
                ['suffix' => 'custom_width', 'kind' => 'integer'],
                ['suffix' => 'custom_height', 'kind' => 'integer'],
                ['suffix' => 'custom_crop', 'kind' => 'boolean'],
            ];
        }
        if ($field->fullscreenEditable) {
            array_push(
                $definitions,
                ['suffix' => 'override_fullscreen_dimensions', 'kind' => 'boolean'],
                ['suffix' => 'custom_fullscreen_width', 'kind' => 'integer'],
                ['suffix' => 'custom_fullscreen_height', 'kind' => 'integer'],
                ['suffix' => 'custom_fullscreen_crop', 'kind' => 'boolean'],
            );
        }

        return $definitions;
    }

    /**
     * @return array<string, string>
     */
    private function getResolvedVariableSuffixes(bool $includeObject): array
    {
        $variables = [
            '' => 'fileID',
            '_alt' => 'alt',
            '_filename' => 'filename',
            '_type' => 'type',
            '_relativePath' => 'relativePath',
            '_link' => 'link',
            '_width' => 'width',
            '_height' => 'height',
            '_fullscreenLink' => 'fullscreenLink',
            '_fullscreenWidth' => 'fullscreenWidth',
            '_fullscreenHeight' => 'fullscreenHeight',
            '_thumbnailLink' => 'thumbnailLink',
            '_thumbnailWidth' => 'thumbnailWidth',
            '_thumbnailHeight' => 'thumbnailHeight',
        ];

        return $includeObject ? ['_object' => 'object', ...$variables] : $variables;
    }
}
