<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Config;

use BlockBuilder\Block\Exception\InvalidConfigFieldDataException;
use BlockBuilder\Block\Exception\UnsupportedConfigSchemaException;
use BlockBuilder\Block\Service\BlockConfigReader;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use ReflectionMethod;

/**
 * Test type: Configuration schema validation component test.
 *
 * Verifies that the block configuration schema rejects unknown top-level properties and field
 * collections that exceed the supported limit.
 */
final class BlockConfigReaderSchemaTest extends BlockBuilderTestCase
{
    /**
     * Confirms that a misspelled or otherwise unknown top-level configuration property is rejected.
     */
    public function testUnknownTopLevelPropertiesAreRejected(): void
    {
        $configPath = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'predefined_configs'
            . DIRECTORY_SEPARATOR
            . 'all_fields.json';
        $contents = file_get_contents($configPath);
        self::assertNotFalse($contents);
        $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        $data['cacheBlockOuput'] = true;

        $this->expectException(UnsupportedConfigSchemaException::class);
        $this->expectExceptionMessage('unsupported property "cacheBlockOuput"');

        $validateSchema = new ReflectionMethod(BlockConfigReader::class, 'validateSchema');
        $validateSchema->invoke(
            $this->getService(BlockConfigReader::class),
            $data,
            $configPath,
        );
    }

    /**
     * Confirms that a configuration cannot contain more fields than the supported collection limit.
     */
    public function testOversizedFieldCollectionsAreRejected(): void
    {
        $configPath = 'oversized/config-bb.json';
        $field = [
            'fieldType' => 'text_field',
            'label' => 'Example field',
            'handle' => 'exampleField',
        ];
        $data = [
            'basic' => array_fill(0, 101, $field),
            'entries' => [],
        ];

        $this->expectException(InvalidConfigFieldDataException::class);
        $this->expectExceptionMessage('may contain at most 100 fields');

        (new ReflectionMethod(BlockConfigReader::class, 'validateFieldCollections'))->invoke(
            $this->getService(BlockConfigReader::class),
            $data,
            $configPath,
        );
    }
}
