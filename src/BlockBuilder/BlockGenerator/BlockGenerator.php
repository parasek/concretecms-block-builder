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
        private BlockGenerationPlanFactory $blockGenerationPlanFactory,
        private GeneratedTextFileWriter $generatedTextFileWriter,
        private FileGeneratorCollection $fileGenerators,
        private BlockIconGenerator $blockIconGenerator,
        private BlockTypeLifecycleService $blockTypeLifecycleService,
    ) {
    }

    public function create(BlockConfigDto $config, BlockGenerationManifest $manifest): BlockGenerationResult
    {
        // Prepare the shared configuration, manifest, and field-generation plan.
        $fileGenerationContext = $this->createFileGenerationContext($config, $manifest);

        // Render every generated text file before modifying the block directory.
        $generatedFiles = $this->collectGeneratedFiles($fileGenerationContext);

        // Prepare a new directory or a recoverable copy of the existing block.
        $directoryTransaction = $this->blockDirectoryManager->prepare($config, $manifest);

        // Generate the block and restore its directory if a pre-lifecycle step fails.
        try {
            // Write all rendered text files to their declared destinations.
            $this->generatedTextFileWriter->write($generatedFiles, $fileGenerationContext);

            // Copy the selected icon or save the uploaded custom icon.
            $this->blockIconGenerator->generate($manifest);

            // Mark the generated files as committed and disable filesystem rollback.
            $directoryTransaction->commitGeneratedFiles();

            // Install a new block type or refresh the existing block type.
            $postGenerationBlockState = $this->blockTypeLifecycleService->installOrRefresh($config, $manifest);

            // Record that the Concrete lifecycle operation completed successfully.
            $directoryTransaction->markLifecycleCompleted();
        } catch (Throwable $throwable) {
            // Handle failures after the filesystem rollback boundary separately.
            if (!$directoryTransaction->canRollback()) {
                // Preserve an exception that already describes a generation failure.
                if ($throwable instanceof BlockGenerationException) {
                    throw $throwable;
                }

                // Wrap an unexpected failure that occurred after files were committed.
                throw new BlockGenerationException(
                    message: sprintf('Unable to complete generation of block "%s" after its generated files were committed.', $config->blockHandle),
                    previous: $throwable,
                );
            }

            // Try to restore the previous folder or remove the partial new folder.
            try {
                $directoryTransaction->rollback();
            } catch (Throwable $rollbackThrowable) {
                // Report both the original failure and the failed restoration.
                throw new BlockGenerationException(
                    message: sprintf(
                        'Block generation and directory rollback failed for block "%s". Rollback error: %s',
                        $config->blockHandle,
                        $rollbackThrowable->getMessage(),
                    ),
                    previous: $throwable,
                );
            }

            // Preserve an exception that already describes a generation failure.
            if ($throwable instanceof BlockGenerationException) {
                throw $throwable;
            }

            // Wrap any other failure in the package-level generation exception.
            throw new BlockGenerationException(
                message: sprintf('Unable to generate block "%s".', $config->blockHandle),
                previous: $throwable,
            );
        }

        // Remove the no-longer-needed backup without failing successful generation.
        $directoryTransaction->cleanupBackup();

        // Return the information needed to display the generation result.
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
                plan: $this->blockGenerationPlanFactory->create($config, $manifest),
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
