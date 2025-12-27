<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\ColorPicker\ColorPickerFieldType;
use BlockBuilder\FieldType\Type\DatePicker\DatePickerFieldType;
use BlockBuilder\FieldType\Type\Express\ExpressFieldType;
use BlockBuilder\FieldType\Type\ExternalLink\ExternalLinkFieldType;
use BlockBuilder\FieldType\Type\FileSet\FileSetFieldType;
use BlockBuilder\FieldType\Type\FilesFromFolder\FilesFromFolderFieldType;
use BlockBuilder\FieldType\Type\FlexLink\FlexLinkFieldType;
use BlockBuilder\FieldType\Type\HtmlEditor\HtmlEditorFieldType;
use BlockBuilder\FieldType\Type\IconPicker\IconPickerFieldType;
use BlockBuilder\FieldType\Type\Image\ImageFieldType;
use BlockBuilder\FieldType\Type\LinkFromFileManager\LinkFromFileManagerFieldType;
use BlockBuilder\FieldType\Type\LinkFromSitemap\LinkFromSitemapFieldType;
use BlockBuilder\FieldType\Type\MultipleChoice\MultipleChoiceFieldType;
use BlockBuilder\FieldType\Type\Number\NumberFieldType;
use BlockBuilder\FieldType\Type\SingleChoice\SingleChoiceFieldType;
use BlockBuilder\FieldType\Type\SvgIconPicker\SvgIconPickerFieldType;
use BlockBuilder\FieldType\Type\Text\TextFieldType;
use BlockBuilder\FieldType\Type\Textarea\TextareaFieldType;
use BlockBuilder\FieldType\Type\UserSelector\UserSelectorFieldType;
use BlockBuilder\FieldType\Type\WysiwygEditor\WysiwygEditorFieldType;

/**
 * Canonical catalog of field types supported by Block Builder.
 *
 * Adding a field type requires an enum case and one explicit registration here.
 * Generation-contributor registration remains separate because it is output-specific.
 */
final class FieldTypeRegistry
{
    /**
     * @var array<string, FieldTypeInterface>
     */
    private array $fieldTypesByHandle = [];

    public function __construct()
    {
        $this->register(new TextFieldType());
        $this->register(new TextareaFieldType());
        $this->register(new NumberFieldType());
        $this->register(new WysiwygEditorFieldType());
        $this->register(new HtmlEditorFieldType());
        $this->register(new SingleChoiceFieldType());
        $this->register(new MultipleChoiceFieldType());
        $this->register(new FlexLinkFieldType());
        $this->register(new LinkFromSitemapFieldType());
        $this->register(new LinkFromFileManagerFieldType());
        $this->register(new ExternalLinkFieldType());
        $this->register(new ImageFieldType());
        $this->register(new FileSetFieldType());
        $this->register(new FilesFromFolderFieldType());
        $this->register(new ExpressFieldType());
        $this->register(new DatePickerFieldType());
        $this->register(new ColorPickerFieldType());
        $this->register(new IconPickerFieldType());
        $this->register(new SvgIconPickerFieldType());
        $this->register(new UserSelectorFieldType());

        if (count($this->fieldTypesByHandle) !== count(FieldTypeEnum::cases())) {
            throw new \LogicException('Every field type enum case must have exactly one registered field type.');
        }
    }

    /**
     * @return FieldTypeInterface[]
     */
    public function all(): array
    {
        return array_values($this->fieldTypesByHandle);
    }

    public function get(FieldTypeEnum $type): FieldTypeInterface
    {
        return $this->fieldTypesByHandle[$type->value];
    }

    public function findByHandle(string $handle): ?FieldTypeInterface
    {
        return $this->fieldTypesByHandle[$handle] ?? null;
    }

    private function register(FieldTypeInterface $fieldType): void
    {
        $type = $fieldType::getFieldType();
        $handle = $type->value;
        if (isset($this->fieldTypesByHandle[$handle])) {
            throw new \LogicException(sprintf('Field type "%s" is registered more than once.', $handle));
        }

        $fieldType::getDtoClass();
        $fieldType::getLegacyPropertyAliases();
        $this->fieldTypesByHandle[$handle] = $fieldType;
    }
}
