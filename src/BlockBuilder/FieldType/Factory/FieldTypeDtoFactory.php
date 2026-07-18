<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Factory;

use BlockBuilder\FieldType\Exception\InvalidFieldDataTypeException;
use BlockBuilder\FieldType\Exception\MalformedFieldDataException;
use BlockBuilder\FieldType\Exception\MissingFieldTypeException;
use BlockBuilder\FieldType\Exception\UnknownFieldTypeException;
use BlockBuilder\FieldType\FieldTypeDtoInterface;
use BlockBuilder\FieldType\FieldTypeRegistry;
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

        foreach ($data as $propertyName => $value) {
            if (is_array($value) || is_object($value) || is_resource($value)) {
                throw new InvalidFieldDataTypeException(
                    sprintf('Property "%s" of field type "%s" has an unsupported data type.', $propertyName, $fieldTypeHandle),
                );
            }
        }

        foreach (['selectOptions', 'selectMultipleOptions'] as $optionListProperty) {
            if (array_key_exists($optionListProperty, $data)
                && !ChoiceOptionListValidator::hasValidShape($data[$optionListProperty])
            ) {
                throw new MalformedFieldDataException(
                    sprintf('Property "%s" of field type "%s" contains malformed choice options.', $optionListProperty, $fieldTypeHandle),
                );
            }
        }

        $fieldType = $this->fieldTypeRegistry->findByHandle($fieldTypeHandle);
        if ($fieldType === null) {
            throw new UnknownFieldTypeException(
                sprintf('Unknown field type "%s".', $fieldTypeHandle),
            );
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
}
