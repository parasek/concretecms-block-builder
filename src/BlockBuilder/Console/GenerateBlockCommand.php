<?php

declare(strict_types=1);

namespace BlockBuilder\Console;

use BlockBuilder\Block\Factory\BlockConfigDtoFactory;
use BlockBuilder\Block\Request\CreateBlockInputNormalizer;
use BlockBuilder\Block\Service\BlockConfigReader;
use BlockBuilder\Block\Validation\CreateBlockValidatorCollection;
use BlockBuilder\BlockGenerator\BlockGenerationManifestFactory;
use BlockBuilder\BlockGenerator\BlockGenerator;
use BlockBuilder\BlockGenerator\Enum\PostGenerationBlockStateEnum;
use Concrete\Core\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\FileBag;

class GenerateBlockCommand extends Command
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
        $this->setName('block-builder:generate')
            ->setDescription('Generate a new application block from a local Block Builder JSON configuration.')
            ->addArgument('config', InputArgument::REQUIRED, 'Local JSON path, absolute or relative to the working directory.')
            ->addOption('validate-only', null, InputOption::VALUE_NONE, 'Validate without writing files or installing the block.')
            ->setHelp('Uses installBlock from the JSON configuration. Existing blocks are never overwritten. Run only with trusted configurations: custom code is copied into executable PHP files.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Console access supplies authorization; Dashboard CSRF and session checks do not apply.
        $config = $this->configReader->getConfigFromFile($input->getArgument('config'));
        $data = json_decode(json_encode($config, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        unset($data['blockBuilderVersion'], $data['concreteVersion'], $data['phpVersion'], $data['createdAt']);
        $data['excludedFromRemoval'] = implode(PHP_EOL, $config->excludedFromRemoval);

        // Adapt the persisted configuration to the same business-validation input as the Dashboard.
        $normalization = $this->inputNormalizer->normalize($data);
        $errors = $normalization->feedback->errors;
        if ($errors === []) {
            $errors = $this->validators->validate($normalization->data, new FileBag())->errors;
        }
        if ($errors !== []) {
            $this->output->error(array_map(OutputFormatter::escape(...), $errors));

            return self::INVALID;
        }

        $config = $this->configFactory->fromGenerationArray($normalization->data);
        $manifest = $this->manifestFactory->create($config, false, null, null);
        if ($input->getOption('validate-only')) {
            $this->output->success(sprintf('Configuration for block "%s" is valid.', $config->blockHandle));

            return self::SUCCESS;
        }

        $result = $this->blockGenerator->generate($config, $manifest);
        $this->output->success(sprintf(
            $result->postGenerationBlockState === PostGenerationBlockStateEnum::CreatedAndInstalled
                ? 'Block "%s" was generated and installed.'
                : 'Block "%s" was generated. Install the block type before adding it to pages.',
            $result->blockHandle,
        ));
        $output->writeln(OutputFormatter::escape($manifest->blockPath));

        return self::SUCCESS;
    }
}
