<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator;

use BlockBuilder\Block\Dto\CreateBlockDto;
use BlockBuilder\Block\Dto\CreateBlockResultDto;
use BlockBuilder\Block\Dto\CreateBlockManifestDto;
use BlockBuilder\Block\Enum\PostGenerationBlockStateEnum;
use BlockBuilder\BlockGenerator\FileGenerator\ConfigBbJson\ConfigBbJsonFileGenerator;
use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\ControllerPhpFileGenerator;
use BlockBuilder\Environment\EnvironmentService;
use BlockBuilder\Service\FileSystemService;
use Concrete\Core\Block\BlockType\BlockType;
use Concrete\Core\Entity\Block\BlockType\BlockType as BlockTypeEntity;
use Concrete\Core\File\Service\File as FileService;
use Doctrine\ORM\EntityManagerInterface;

readonly class BlockGenerator
{
    public function __construct(
        private FileService $fileService,
        private EntityManagerInterface $em,
        private ConfigBbJsonFileGenerator $configBbJsonFileGenerator,
        private ControllerPhpFileGenerator $controllerPhpFileGenerator,
        private FileSystemService $fileSystemService,
        private EnvironmentService $environmentService,
    ) {
    }

    /**
     * EXPLANATION OF BLOCK GENERATION LOGIC
     *
     * For each generated file we are using a combination of a Strategy and Visitor pattern.
     *
     * @see \BlockBuilder\BlockGenerator\BlockGenerator
     *
     * Example of generating a 'controller.php' file:
     *
     * 1. We are passing block config DTO to the output method of ControllerPhpFileGenerator.
     * @see \BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\ControllerPhpFileGenerator
     *
     * 2. In ControllerPhpFileGenerator we are looping through all basic/entry fields.
     * All of them are DTOs that represent specific field types (like 'text' or 'textarea' etc.).
     *
     * 3. For each field, we use a Factory to resolve its specific Strategy.
     * For example, ControllerPhpTextStrategy is used for the 'text' field (TextFieldType).
     * @see \BlockBuilder\FieldType\FieldType\TextFieldType\TextFieldType
     * @see \BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Strategy\ControllerPhpTextStrategy
     *
     * 4. This Strategy is then passed to the Visitor (ControllerPhpVisitor), which "visits" the strategy
     * to extract only the data relevant for that specific file (like 'use' statements, searchable
     * fields, or property declarations -> all of them are needed for controller.php creation).
     * @see \BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Visitor\ControllerPhpVisitor
     *
     * 5. Finally, the Generator retrieves the aggregated data from the Visitor and
     * injects it into the skeleton template using a Formatter service.
     * @see \BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\Service\ControllerPhpFormatterService
     * @see /public/packages/block_builder/generator_templates/skeletons/controller.php.stub
     *
     * How to add a new field type:
     * @see \BlockBuilder\FieldType\Enum\FieldTypeEnum
     */
    public function create(CreateBlockDto $dto, CreateBlockManifestDto $manifestDto): CreateBlockResultDto
    {
        if ($manifestDto->shouldBlockBeRebuilt) {
            $this->prepareDirectoryForRebuild($dto, $manifestDto);
        }

        if (!file_exists($manifestDto->blockPath)) {
            mkdir($manifestDto->blockPath);
        }

        $this->generateConfigBbJson($dto, $manifestDto);
        $this->generateControllerPhp($dto, $manifestDto);
        $this->generateIconPng($dto, $manifestDto);

        if ($manifestDto->shouldBlockBeRebuilt) {
            $postGenerationBlockState = PostGenerationBlockStateEnum::Rebuilt;
            $bt = BlockType::getByHandle($dto->blockHandle);
            $bt = $this->em->find(BlockTypeEntity::class, $bt->getBlockTypeID());
            $bt->refresh();
        } elseif ($manifestDto->shouldBlockBeInstalled) {
            $postGenerationBlockState = PostGenerationBlockStateEnum::CreatedAndInstalled;
            BlockType::installBlockType($dto->blockHandle);
        } else {
            $postGenerationBlockState = PostGenerationBlockStateEnum::Created;
        }

        return new CreateBlockResultDto(
            blockName: $dto->blockName,
            blockHandle: $dto->blockHandle,
            postGenerationBlockState: $postGenerationBlockState,
        );
    }

    private function generateConfigBbJson(CreateBlockDto $dto, CreateBlockManifestDto $manifestDto): void
    {
        $output = $this->configBbJsonFileGenerator->getOutput($dto, $manifestDto);
        $this->createFile(
            path: DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $dto->blockHandle . DIRECTORY_SEPARATOR . EnvironmentService::CONFIG_BB_JS,
            content: $output,
        );
    }

    private function generateControllerPhp(CreateBlockDto $dto, CreateBlockManifestDto $manifestDto): void
    {
        $output = $this->controllerPhpFileGenerator->getOutput($dto, $manifestDto);
        $this->createFile(
            path: DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $dto->blockHandle . DIRECTORY_SEPARATOR . FILENAME_BLOCK_CONTROLLER,
            content: $output,
        );
    }

    private function generateIconPng(CreateBlockDto $dto, CreateBlockManifestDto $manifestDto): void
    {
        $to = DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR
            . $dto->blockHandle . DIRECTORY_SEPARATOR
            . FILENAME_BLOCK_ICON;

        if ($manifestDto->customBlockIcon) {
            $manifestDto->customBlockIcon->move(
                dirname($to),
                basename($to)
            );
        } elseif ($manifestDto->blockIconPublicPath) {
            $path = DIR_BASE . $manifestDto->blockIconPublicPath;
            if (file_exists($path)) {
                copy(
                    from: $path,
                    to: $to,
                );
            }
        }

        // If for some reason, the icon was not generated, copy the default one.
        // It can happen when uninstalling a block type / removing the block type folder with js call (alert message).
        if (!file_exists($to)) {
            copy(
                from: DIR_BASE . $this->environmentService->getPublicPathToDefaultBlockIcon(),
                to: $to,
            );
        }
    }

    private function createFile($path, $content): void
    {
        $this->fileService->append($path, $content);
    }

    private function prepareDirectoryForRebuild(CreateBlockDto $dto, CreateBlockManifestDto $manifestDto): void
    {
        // We want to exclude "icon.png" from being removed
        // when rebuilding block, and the option "Keep current icon" is selected
        $excludedFromRemoval = $dto->excludedFromRemoval;
        $needle = DIRECTORY_SEPARATOR . DIRNAME_APPLICATION . DIRECTORY_SEPARATOR . DIRNAME_BLOCKS;
        if (str_starts_with(haystack: $manifestDto->blockIconPublicPath, needle: $needle)) {
            $excludedFromRemoval = array_merge($excludedFromRemoval, [FILENAME_BLOCK_ICON]);
        }

        $this->fileSystemService->removeDirectory(
            dir: DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $dto->blockHandle,
            excluded: $excludedFromRemoval,
        );
    }
}
