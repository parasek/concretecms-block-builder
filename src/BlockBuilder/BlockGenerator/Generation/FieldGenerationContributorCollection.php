<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation;

use ArrayIterator;
use BlockBuilder\FieldType\Type\Text\Generation\TextFieldGenerationContributor;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, FieldGenerationContributorInterface>
 */
readonly class FieldGenerationContributorCollection implements IteratorAggregate
{
    public function __construct(
        private TextFieldGenerationContributor $textFieldGenerationContributor,
    ) {
    }

    /**
     * @return Traversable<int, FieldGenerationContributorInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator([
            $this->textFieldGenerationContributor,
        ]);
    }
}
