<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Enum;

use BlockBuilder\FieldType\FieldType\ColorPickerFieldType\ColorPickerFieldType;
use BlockBuilder\FieldType\FieldType\DatePickerFieldType\DatePickerFieldType;
use BlockBuilder\FieldType\FieldType\ExpressFieldType\ExpressFieldType;
use BlockBuilder\FieldType\FieldType\ExternalLinkFieldType\ExternalLinkFieldType;
use BlockBuilder\FieldType\FieldType\FileSetFieldType\FileSetFieldType;
use BlockBuilder\FieldType\FieldType\FlexLinkFieldType\FlexLinkFieldType;
use BlockBuilder\FieldType\FieldType\HtmlEditorFieldType\HtmlEditorFieldType;
use BlockBuilder\FieldType\FieldType\IconPickerFieldType\IconPickerFieldType;
use BlockBuilder\FieldType\FieldType\ImageFieldType\ImageFieldType;
use BlockBuilder\FieldType\FieldType\LinkFromFileManagerFieldType\LinkFromFileManagerFieldType;
use BlockBuilder\FieldType\FieldType\LinkFromSitemapFieldType\LinkFromSitemapFieldType;
use BlockBuilder\FieldType\FieldType\MultipleChoiceFieldType\MultipleChoiceFieldType;
use BlockBuilder\FieldType\FieldType\SingleChoiceFieldType\SingleChoiceFieldType;
use BlockBuilder\FieldType\FieldType\TextFieldType\TextFieldType;
use BlockBuilder\FieldType\FieldType\WysiwygEditorFieldType\WysiwygEditorFieldType;
use BlockBuilder\FieldType\FieldTypeDtoInterface;
use BlockBuilder\FieldType\FieldTypeInterface;
use BlockBuilder\FieldType\FieldType\TextareaFieldType\TextareaFieldType;
use BlockBuilder\FieldType\FieldType\NumberFieldType\NumberFieldType;
use InvalidArgumentException;
use JsonSerializable;

/**
 * HOW TO ADD A NEW FIELD TYPE:
 *
 * @see \BlockBuilder\FieldType\Enum\FieldTypeEnum
 *
 * Typical field type requires listed files/modifications:
 *
 * 1) Case entry in FieldTypeEnum
 * @see \BlockBuilder\FieldType\Enum\FieldTypeEnum
 *
 * 2) Strategy for each generated file (controller.php, db.xml, etc.) where the field type is needed/used.
 * Each strategy should implement the respective FieldTypeStrategyInterface.
 * @see \BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpTextStrategy
 * @see \BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpFieldTypeStrategyInterface
 *
 * 3) FieldType class that implements FieldTypeInterface.
 *
 * Important!
 * If you add a custom property to Field Type (for example, "height" in WysiwygEditorFieldType),
 * the "height" handle should be unique across all field types.
 * That's why it is actually named "wysiwygEditorHeight"
 * -> to not collide with the "textareaHeight" property in TextareaFieldType.
 * @see \BlockBuilder\FieldType\FieldType\TextFieldType\TextFieldType
 * @see \BlockBuilder\FieldType\FieldTypeInterface
 *
 * 4) FieldTypeDto class that implements FieldTypeDtoInterface.
 * DTO maps all fields that are used in block creation form.
 * @see \BlockBuilder\FieldType\FieldType\TextFieldType\TextFieldTypeDto
 * @see \BlockBuilder\FieldType\FieldTypeDtoInterface
 *
 * 5) Entry in FieldTypeEnum in getDefinitionClass() method
 * @see \BlockBuilder\FieldType\Enum\FieldTypeEnum::getDefinitionClass()
 *
 * Further read on general concept of block generation:
 * @see \BlockBuilder\BlockGenerator\BlockGenerator
 */
enum FieldTypeEnum implements JsonSerializable
{
    case Text;
    case Textarea;
    case Number;
    case WysiwygEditor;
    case SingleChoice;
    case MultipleChoice;
    case FlexLink;
    case LinkFromSitemap;
    case LinkFromFileManager;
    case ExternalLink;
    case Image;
    case Express;
    case FileSet;
    case HtmlEditor;
    case DatePicker;
    case ColorPicker;
    case IconPicker;

    public function getDefinitionClass(): string
    {
        return match ($this) {
            self::Text => TextFieldType::class,
            self::Textarea => TextareaFieldType::class,
            self::Number => NumberFieldType::class,
            self::WysiwygEditor => WysiwygEditorFieldType::class,
            self::SingleChoice => SingleChoiceFieldType::class,
            self::MultipleChoice => MultipleChoiceFieldType::class,
            self::FlexLink => FlexLinkFieldType::class,
            self::LinkFromSitemap => LinkFromSitemapFieldType::class,
            self::LinkFromFileManager => LinkFromFileManagerFieldType::class,
            self::ExternalLink => ExternalLinkFieldType::class,
            self::Image => ImageFieldType::class,
            self::Express => ExpressFieldType::class,
            self::FileSet => FileSetFieldType::class,
            self::HtmlEditor => HtmlEditorFieldType::class,
            self::DatePicker => DatePickerFieldType::class,
            self::ColorPicker => ColorPickerFieldType::class,
            self::IconPicker => IconPickerFieldType::class,
        };
    }

    public function getInstance(): FieldTypeInterface
    {
        $class = $this->getDefinitionClass();
        return new $class();
    }

    public function createDtoFromArray(array $data): FieldTypeDtoInterface
    {
        /** @var FieldTypeInterface $class */
        $class = $this->getDefinitionClass();

        return $class::createDtoFromArray($data);
    }

    public function getHandle(): string
    {
        /** @var FieldTypeInterface $class */
        $class = $this->getDefinitionClass();

        return $class::getHandle();
    }

    public function getLabel(): string
    {
        /** @var FieldTypeInterface $class */
        $class = $this->getDefinitionClass();

        return $class::getLabel();
    }

    public function getIcon(): string
    {
        /** @var FieldTypeInterface $class */
        $class = $this->getDefinitionClass();

        return $class::getIcon();
    }

    public static function fromHandle(string $handle): self
    {
        foreach (self::cases() as $case) {
            $class = $case->getDefinitionClass();
            /** @var FieldTypeInterface $class */
            if ($class::getHandle() === $handle) {
                return $case;
            }
        }

        throw new InvalidArgumentException("Unknown field type handle: $handle");
    }

    public function jsonSerialize(): string
    {
        return $this->getHandle();
    }
}
