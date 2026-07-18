<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator;

use BlockBuilder\Block\Dto\BlockConfigDto;
use BlockBuilder\Block\Dto\BlockGenerationManifest;
use BlockBuilder\Block\Dto\BlockGenerationResult;
use BlockBuilder\BlockGenerator\Directory\BlockDirectoryManager;
use BlockBuilder\BlockGenerator\Exception\BlockGenerationException;
use BlockBuilder\BlockGenerator\FileGenerator\FileGeneratorCollection;
use BlockBuilder\BlockGenerator\FileGenerator\GeneratedTextFileCollection;
use BlockBuilder\BlockGenerator\Generation\BlockGenerationPlanFactory;
use BlockBuilder\BlockGenerator\Lifecycle\BlockTypeLifecycleService;
use Throwable;

readonly class BlockGenerator
{
    public function __construct(
        private BlockDirectoryManager $blockDirectoryManager,
        private BlockGenerationPlanFactory $generationPlanFactory,
        private GeneratedTextFileWriter $generatedTextFileWriter,
        private FileGeneratorCollection $fileGenerators,
        private BlockIconGenerator $blockIconGenerator,
        private BlockTypeLifecycleService $blockTypeLifecycleService,
    ) {
    }

    public function create(BlockConfigDto $config, BlockGenerationManifest $manifest): BlockGenerationResult
    {
        $fileGenerationContext = $this->createFileGenerationContext($config, $manifest);
        $generatedFiles = $this->collectGeneratedFiles($fileGenerationContext);
        $directoryTransaction = $this->blockDirectoryManager->prepare($config, $manifest);

        try {
            $this->generatedTextFileWriter->write($generatedFiles, $fileGenerationContext);
            $this->blockIconGenerator->generate($manifest);
            $directoryTransaction->commitGeneratedFiles();
            $postGenerationBlockState = $this->blockTypeLifecycleService->apply($config, $manifest);
            $directoryTransaction->markLifecycleApplied();
        } catch (Throwable $throwable) {
            if (!$directoryTransaction->canRollback()) {
                if ($throwable instanceof BlockGenerationException) {
                    throw $throwable;
                }

                throw new BlockGenerationException(
                    message: sprintf('Unable to complete generation of block "%s" after its generated files were committed.', $config->blockHandle),
                    previous: $throwable,
                );
            }

            try {
                $directoryTransaction->rollback();
            } catch (Throwable $rollbackThrowable) {
                throw new BlockGenerationException(
                    message: sprintf(
                        'Block generation and directory rollback failed for block "%s". Rollback error: %s',
                        $config->blockHandle,
                        $rollbackThrowable->getMessage(),
                    ),
                    previous: $throwable,
                );
            }

            if ($throwable instanceof BlockGenerationException) {
                throw $throwable;
            }

            throw new BlockGenerationException(
                message: sprintf('Unable to generate block "%s".', $config->blockHandle),
                previous: $throwable,
            );
        }

        $directoryTransaction->cleanupBackup();

        return new BlockGenerationResult(
            blockName: $config->blockName,
            blockHandle: $config->blockHandle,
            postGenerationBlockState: $postGenerationBlockState,
        );
    }

    private function createFileGenerationContext(
        BlockConfigDto $config,
        BlockGenerationManifest $manifest,
    ): BlockFileGenerationContext {
        try {
            return new BlockFileGenerationContext(
                config: $config,
                manifest: $manifest,
                plan: $this->generationPlanFactory->create($config, $manifest),
            );
        } catch (BlockGenerationException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw new BlockGenerationException(
                message: sprintf('Unable to prepare the generation plan for block "%s".', $config->blockHandle),
                previous: $throwable,
            );
        }
    }

    private function collectGeneratedFiles(BlockFileGenerationContext $context): GeneratedTextFileCollection
    {
        $generatedFiles = new GeneratedTextFileCollection();

        try {
            foreach ($this->fileGenerators as $generator) {
                $generatedFiles->addAll($generator->generate($context));
            }
        } catch (BlockGenerationException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw new BlockGenerationException(
                message: sprintf('Unable to render generated files for block "%s".', $context->config->blockHandle),
                previous: $throwable,
            );
        }

        return $generatedFiles;
    }
}
