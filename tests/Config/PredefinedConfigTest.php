<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Config;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;

/**
 * Test type: Predefined configuration contract test.
 *
 * Verifies that shipped configuration presets use the canonical schema and collectively cover
 * every registered field type.
 */
final class PredefinedConfigTest extends BlockBuilderTestCase
{
    /**
     * Confirms that each shipped preset uses current metadata, serializes canonically, and includes every field type.
     *
     * @dataProvider predefinedConfigProvider
     */
    public function testPredefinedConfigUsesCanonicalSchemaAndCoversEveryFieldType(
        string $fileName,
        int $expectedFieldsPerContext,
    ): void {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'predefined_configs' . DIRECTORY_SEPARATOR . $fileName;
        $contents = file_get_contents($path);
        self::assertNotFalse($contents);
        $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        self::assertSame('3.0.0', $data['blockBuilderVersion']);
        self::assertSame('9.5.2', $data['concreteVersion']);
        self::assertSame('8.4', $data['phpVersion']);
        self::assertArrayNotHasKey('version', $data);
        self::assertArrayNotHasKey('urlEndingHelpText', $data);
        self::assertIsBool($data['installBlock']);
        self::assertIsInt($data['blockWidth']);

        $expectedFieldTypes = array_map(
            static fn(FieldTypeEnum $fieldType): string => $fieldType->value,
            FieldTypeEnum::cases(),
        );
        sort($expectedFieldTypes);

        foreach (['basic', 'entries'] as $collectionName) {
            self::assertTrue(array_is_list($data[$collectionName]));
            self::assertCount($expectedFieldsPerContext, $data[$collectionName]);

            $actualFieldTypes = array_values(array_unique(array_column($data[$collectionName], 'fieldType')));
            sort($actualFieldTypes);
            self::assertSame($expectedFieldTypes, $actualFieldTypes);
        }

        $config = $this->createBlockConfigDtoFactory()->fromArray($data);
        self::assertSame(
            rtrim($contents, "\r\n"),
            json_encode($config, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
    }

    public static function predefinedConfigProvider(): array
    {
        return [
            'one of each field' => ['all_fields.json', 24],
            'two of each field' => ['all_fields_double.json', 48],
        ];
    }
}
