<?php

declare(strict_types=1);

namespace BlockBuilder\Console;

use BlockBuilder\Block\Factory\BlockConfigDtoFactory;
use BlockBuilder\Block\Request\CreateBlockInputNormalizer;
use BlockBuilder\Block\Service\BlockConfigReader;
use BlockBuilder\Block\Validation\CreateBlockValidatorCollection;
use BlockBuilder\BlockGenerator\BlockGenerationManifestFactory;
use BlockBuilder\BlockGenerator\BlockGenerator;
use Concrete\Core\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\FileBag;

class RebuildBlockCommand extends Command
{
    public function __construct(
        private readonly BlockConfigReader $configReader,
        private readonly CreateBlockInputNormalizer $inputNormalizer,
        private readonly CreateBlockValidatorCollection $validators,
        private readonly BlockConfigDtoFactory $configFactory,
        private readonly BlockGenerationManifestFactory $manifestFactory,
        private readonly BlockGenerator $blockGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('block-builder:rebuild')
            ->setDescription('Rebuild and refresh an installed application block from its config-bb.json.')
            ->addArgument('handle', InputArgument::REQUIRED, 'Handle of an installed application block created by Block Builder.')
            ->addOption('validate-only', null, InputOption::VALUE_NONE, 'Validate without writing files or refreshing the block.')
            ->setHelp('Replaces generated files and refreshes the installed block type, regardless of installBlock. Review excludedFromRemoval and custom code before rebuilding. Run only with trusted configurations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Console access supplies authorization; Dashboard CSRF and session checks do not apply.
        $blockHandle = $input->getArgument('handle');
        $config = $this->configReader->getConfigFromApplicationFolder($blockHandle);
        $data = json_decode(json_encode($config, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        unset($data['blockBuilderVersion'], $data['concreteVersion'], $data['phpVersion'], $data['createdAt']);
        $data['excludedFromRemoval'] = implode(PHP_EOL, $config->excludedFromRemoval);
        $data['rebuildBlock'] = true;

        // Adapt the persisted configuration to the same business-validation input as the Dashboard.
        $normalization = $this->inputNormalizer->normalize($data);
        $errors = $normalization->feedback->errors;
        if ($errors === []) {
            $validationData = $normalization->data;
            $validationData['rebuildSourceHandle'] = $blockHandle;
            $errors = $this->validators->validate($validationData, new FileBag())->errors;
        }
        if ($errors !== []) {
            $this->output->error(array_map(OutputFormatter::escape(...), $errors));

            return self::INVALID;
        }

        $config = $this->configFactory->fromGenerationArray($normalization->data);
        $blockIconPublicPath = '/' . DIRNAME_APPLICATION . '/' . DIRNAME_BLOCKS . '/' . $blockHandle . '/' . FILENAME_BLOCK_ICON;
        $manifest = $this->manifestFactory->create($config, true, $blockIconPublicPath, null);
        if ($input->getOption('validate-only')) {
            $this->output->success(sprintf('Configuration for block "%s" is valid.', $config->blockHandle));

            return self::SUCCESS;
        }

        $result = $this->blockGenerator->generate($config, $manifest);
        $this->output->success(sprintf('Block "%s" was rebuilt and refreshed.', $result->blockHandle));
        $output->writeln(OutputFormatter::escape($manifest->blockPath));

        return self::SUCCESS;
    }
}
