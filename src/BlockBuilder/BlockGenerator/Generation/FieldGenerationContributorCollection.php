<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation;

use ArrayIterator;
use BlockBuilder\FieldType\Type\Number\Generation\NumberFieldGenerationContributor;
use BlockBuilder\FieldType\Type\Text\Generation\TextFieldGenerationContributor;
use BlockBuilder\FieldType\Type\Textarea\Generation\TextareaFieldGenerationContributor;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, FieldGenerationContributorInterface>
 */
readonly class FieldGenerationContributorCollection implements IteratorAggregate
{
    public function __construct(
        private NumberFieldGenerationContributor $numberFieldGenerationContributor,
        private TextFieldGenerationContributor $textFieldGenerationContributor,
        private TextareaFieldGenerationContributor $textareaFieldGenerationContributor,
    ) {
    }

    /**
     * @return Traversable<int, FieldGenerationContributorInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator([
            $this->numberFieldGenerationContributor,
            $this->textFieldGenerationContributor,
            $this->textareaFieldGenerationContributor,
        ]);
    }
}
