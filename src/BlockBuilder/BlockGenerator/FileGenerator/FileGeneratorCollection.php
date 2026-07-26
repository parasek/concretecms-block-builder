<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\FileGenerator;

use ArrayIterator;
use BlockBuilder\BlockGenerator\FileGenerator\AutoCss\AutoCssFileGenerator;
use BlockBuilder\BlockGenerator\FileGenerator\AutoJs\AutoJsFileGenerator;
use BlockBuilder\BlockGenerator\FileGenerator\ConfigBbJson\ConfigBbJsonFileGenerator;
use BlockBuilder\BlockGenerator\FileGenerator\ControllerPhp\ControllerPhpFileGenerator;
use BlockBuilder\BlockGenerator\FileGenerator\DbXml\DbXmlFileGenerator;
use BlockBuilder\BlockGenerator\FileGenerator\FormPhp\FormPhpFileGenerator;
use BlockBuilder\BlockGenerator\FileGenerator\Scaffold\ScaffoldFileGenerator;
use BlockBuilder\BlockGenerator\FileGenerator\ViewPhp\ViewPhpFileGenerator;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, FileGeneratorInterface>
 */
readonly class FileGeneratorCollection implements IteratorAggregate
{
    public function __construct(
        private ConfigBbJsonFileGenerator $configBbJsonFileGenerator,
        private ScaffoldFileGenerator $scaffoldFileGenerator,
        private ControllerPhpFileGenerator $controllerPhpFileGenerator,
        private DbXmlFileGenerator $dbXmlFileGenerator,
        private FormPhpFileGenerator $formPhpFileGenerator,
        private ViewPhpFileGenerator $viewPhpFileGenerator,
        private AutoJsFileGenerator $autoJsFileGenerator,
        private AutoCssFileGenerator $autoCssFileGenerator,
    ) {
    }

    /**
     * @return Traversable<int, FileGeneratorInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator([
            $this->configBbJsonFileGenerator,
            $this->scaffoldFileGenerator,
            $this->controllerPhpFileGenerator,
            $this->dbXmlFileGenerator,
            $this->formPhpFileGenerator,
            $this->viewPhpFileGenerator,
            $this->autoJsFileGenerator,
            $this->autoCssFileGenerator,
        ]);
    }
}
