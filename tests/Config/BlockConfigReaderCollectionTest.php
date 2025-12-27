<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Config;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Exception\InvalidConfigJsonException;
use BlockBuilder\Block\Service\BlockConfigReader;
use BlockBuilder\Block\Service\BlockDirectoryLocator;
use BlockBuilder\Environment\EnvironmentService;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;

/**
 * Test type: Application configuration collection component test.
 *
 * Verifies that one invalid application configuration does not hide other valid block configs.
 */
final class BlockConfigReaderCollectionTest extends BlockBuilderTestCase
{
    private string $applicationBlocksDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->applicationBlocksDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'block-builder-config-collection-'
            . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($this->applicationBlocksDirectory, 0700));
    }

    protected function tearDown(): void
    {
        $blockDirectories = scandir($this->applicationBlocksDirectory);
        if (is_array($blockDirectories)) {
            foreach ($blockDirectories as $blockDirectoryName) {
                if ($blockDirectoryName === '.' || $blockDirectoryName === '..') {
                    continue;
                }

                $blockDirectory = $this->applicationBlocksDirectory . DIRECTORY_SEPARATOR . $blockDirectoryName;
                $configPath = $blockDirectory . DIRECTORY_SEPARATOR . EnvironmentService::CONFIG_BB_JSON;
                if (is_file($configPath)) {
                    unlink($configPath);
                }
                if (is_dir($blockDirectory)) {
                    rmdir($blockDirectory);
                }
            }
        }
        rmdir($this->applicationBlocksDirectory);

        parent::tearDown();
    }

    public function testInvalidConfigDoesNotHideValidApplicationConfigs(): void
    {
        $this->writeValidConfig('valid_older', '2025-01-01 12:00:00');
        $this->writeRawConfig('broken_config', '{"basic":');
        $this->writeValidConfig('valid_newer', '2026-01-01 12:00:00');
        self::assertTrue(mkdir($this->applicationBlocksDirectory . DIRECTORY_SEPARATOR . 'without_config', 0700));

        $result = $this->createReader()->getConfigsFromApplicationFolder();

        self::assertSame(
            ['valid_newer', 'valid_older'],
            array_map(static fn(BlockConfigDto $config): string => $config->blockHandle, $result->configs),
        );
        self::assertCount(1, $result->errors);
        self::assertInstanceOf(InvalidConfigJsonException::class, $result->errors[0]);
        self::assertStringContainsString('broken_config/config-bb.json', $result->errors[0]->getMessage());
    }

    private function createReader(): BlockConfigReader
    {
        $applicationBlocksDirectory = $this->applicationBlocksDirectory;
        $blockDirectoryLocator = new readonly class($applicationBlocksDirectory) extends BlockDirectoryLocator {
            public function __construct(private string $applicationBlocksDirectory)
            {
            }

            public function getApplicationBlocksPath(): string
            {
                return $this->applicationBlocksDirectory;
            }
        };

        return new BlockConfigReader(
            blockConfigDtoFactory: $this->createBlockConfigDtoFactory(),
            environmentService: $this->getService(EnvironmentService::class),
            blockDirectoryLocator: $blockDirectoryLocator,
        );
    }

    private function writeValidConfig(string $blockHandle, string $createdAt): void
    {
        $sourcePath = dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . 'predefined_configs'
            . DIRECTORY_SEPARATOR
            . 'all_fields.json';
        $contents = file_get_contents($sourcePath);
        self::assertNotFalse($contents);
        $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        $data['blockHandle'] = $blockHandle;
        $data['blockName'] = ucwords(str_replace('_', ' ', $blockHandle));
        $data['createdAt'] = $createdAt;

        $this->writeRawConfig($blockHandle, json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function writeRawConfig(string $blockHandle, string $contents): void
    {
        $blockDirectory = $this->applicationBlocksDirectory . DIRECTORY_SEPARATOR . $blockHandle;
        self::assertTrue(mkdir($blockDirectory, 0700));
        $configPath = $blockDirectory . DIRECTORY_SEPARATOR . EnvironmentService::CONFIG_BB_JSON;
        self::assertSame(strlen($contents), file_put_contents($configPath, $contents));
    }
}
