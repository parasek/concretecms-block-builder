<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Factory;

use BlockBuilder\FieldType\Exception\ConflictingFieldPropertyAliasException;
use BlockBuilder\FieldType\Exception\InvalidFieldDataTypeException;
use BlockBuilder\FieldType\Exception\MalformedFieldDataException;
use BlockBuilder\FieldType\Exception\MissingFieldTypeException;
use BlockBuilder\FieldType\Exception\UnknownFieldTypeException;
use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\FieldTypeDtoInterface;
use BlockBuilder\FieldType\FieldTypeInterface;
use BlockBuilder\FieldType\FieldTypeRegistry;
use BlockBuilder\FieldType\Type\SvgIconPicker\SvgIconSanitizer;
use BlockBuilder\FieldType\Validation\ChoiceOptionListValidator;
use ErrorException;
use Throwable;
use TypeError;

class FieldTypeDtoFactory
{
    public function __construct(private readonly FieldTypeRegistry $fieldTypeRegistry)
    {
    }

    public function fromArray(array $data): FieldTypeDtoInterface
    {
        return $this->createDto($data, true);
    }

    /**
     * Creates a DTO for redisplaying normalized form data that may contain business-validation errors.
     */
    public function fromFormArray(array $data): FieldTypeDtoInterface
    {
        return $this->createDto($data, false);
    }

    private function createDto(array $data, bool $validateFieldData): FieldTypeDtoInterface
    {
        if (!array_key_exists('fieldType', $data)) {
            throw new MissingFieldTypeException('Field data does not contain a "fieldType" property.');
        }

        if (!is_string($data['fieldType'])) {
            throw new InvalidFieldDataTypeException('The "fieldType" property must be a string.');
        }

        $fieldTypeHandle = trim($data['fieldType']);
        if ($fieldTypeHandle === '') {
            throw new MissingFieldTypeException('The "fieldType" property cannot be empty.');
        }

        $fieldType = $this->fieldTypeRegistry->findByHandle($fieldTypeHandle);
        if ($fieldType === null) {
            throw new UnknownFieldTypeException(
                sprintf('Unknown field type "%s".', $fieldTypeHandle),
            );
        }

        $data = $this->normalizeLegacyProperties($data, $fieldType, $fieldTypeHandle);

        $unsupportedProperties = array_diff(array_keys($data), $fieldType::getProperties());
        if ($unsupportedProperties !== []) {
            throw new MalformedFieldDataException(sprintf(
                'Field type "%s" contains an unsupported property "%s".',
                $fieldTypeHandle,
                (string) reset($unsupportedProperties),
            ));
        }

        foreach ($data as $propertyName => $value) {
            if (
                is_array($value)
                && $propertyName === 'icons'
                && $fieldTypeHandle === FieldTypeEnum::SvgIconPicker->value
            ) {
                $this->validateSvgIconDefinitions($value, $fieldTypeHandle);
                continue;
            }

            if (is_array($value) || is_object($value) || is_resource($value)) {
                throw new InvalidFieldDataTypeException(
                    sprintf('Property "%s" of field type "%s" has an unsupported data type.', $propertyName, $fieldTypeHandle),
                );
            }
        }

        if ($validateFieldData) {
            if ($fieldTypeHandle === FieldTypeEnum::SvgIconPicker->value) {
                $data['icons'] = $this->sanitizeSvgIconDefinitions($data['icons'] ?? [], $fieldTypeHandle);
            }

            foreach (['options'] as $optionListProperty) {
                if (array_key_exists($optionListProperty, $data)
                    && !ChoiceOptionListValidator::hasValidShape($data[$optionListProperty])
                ) {
                    throw new MalformedFieldDataException(
                        sprintf('Property "%s" of field type "%s" contains malformed choice options.', $optionListProperty, $fieldTypeHandle),
                    );
                }
            }
        }

        set_error_handler(
            static function (int $severity, string $message, string $file, int $line): never {
                throw new ErrorException($message, 0, $severity, $file, $line);
            },
            E_WARNING | E_NOTICE,
        );

        try {
            return $fieldType::createDtoFromArray($data);
        } catch (TypeError $exception) {
            throw new InvalidFieldDataTypeException(
                message: sprintf('Field data for type "%s" contains a value with an invalid data type.', $fieldTypeHandle),
                previous: $exception,
            );
        } catch (Throwable $throwable) {
            throw new MalformedFieldDataException(
                message: sprintf('Field data for type "%s" is malformed.', $fieldTypeHandle),
                previous: $throwable,
            );
        } finally {
            restore_error_handler();
        }
    }

    private function validateSvgIconDefinitions(array $icons, string $fieldTypeHandle): void
    {
        if (!array_is_list($icons)) {
            throw new MalformedFieldDataException(
                sprintf('Property "icons" of field type "%s" must be a list.', $fieldTypeHandle),
            );
        }

        foreach ($icons as $icon) {
            if (
                !is_array($icon)
                || count($icon) !== 3
                || array_diff(['name', 'handle', 'svg'], array_keys($icon)) !== []
            ) {
                throw new MalformedFieldDataException(
                    sprintf('Property "icons" of field type "%s" contains a malformed icon definition.', $fieldTypeHandle),
                );
            }
            foreach ($icon as $value) {
                if (!is_string($value)) {
                    throw new InvalidFieldDataTypeException(
                        sprintf('Property "icons" of field type "%s" contains a value with an invalid data type.', $fieldTypeHandle),
                    );
                }
            }
        }
    }

    private function sanitizeSvgIconDefinitions(array $icons, string $fieldTypeHandle): array
    {
        foreach ($icons as $iconIndex => $icon) {
            $sanitizedSvg = SvgIconSanitizer::sanitize($icon['svg']);
            if ($sanitizedSvg === null) {
                throw new MalformedFieldDataException(
                    sprintf(
                        'Property "icons" of field type "%s" contains invalid SVG content at index %s.',
                        $fieldTypeHandle,
                        $iconIndex,
                    ),
                );
            }
            $icons[$iconIndex]['svg'] = $sanitizedSvg;
        }

        return $icons;
    }

    private function normalizeLegacyProperties(
        array $data,
        FieldTypeInterface $fieldType,
        string $fieldTypeHandle,
    ): array {
        foreach ($fieldType::getLegacyPropertyAliases() as $legacyProperty => $canonicalProperty) {
            if (!array_key_exists($legacyProperty, $data)) {
                continue;
            }

            if (
                array_key_exists($canonicalProperty, $data)
                && $data[$canonicalProperty] !== $data[$legacyProperty]
            ) {
                throw new ConflictingFieldPropertyAliasException(sprintf(
                    'Field type "%s" contains conflicting values for legacy property "%s" and canonical property "%s".',
                    $fieldTypeHandle,
                    $legacyProperty,
                    $canonicalProperty,
                ));
            }

            if (!array_key_exists($canonicalProperty, $data)) {
                $data[$canonicalProperty] = $data[$legacyProperty];
            }
            unset($data[$legacyProperty]);
        }

        return $data;
    }
}
