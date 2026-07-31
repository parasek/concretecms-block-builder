<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation;

use ArrayIterator;
use BlockBuilder\FieldType\Type\HtmlEditor\Generation\HtmlEditorFieldGenerationContributor;
use BlockBuilder\FieldType\Type\Number\Generation\NumberFieldGenerationContributor;
use BlockBuilder\FieldType\Type\SingleChoice\Generation\SingleChoiceFieldGenerationContributor;
use BlockBuilder\FieldType\Type\Text\Generation\TextFieldGenerationContributor;
use BlockBuilder\FieldType\Type\Textarea\Generation\TextareaFieldGenerationContributor;
use BlockBuilder\FieldType\Type\WysiwygEditor\Generation\WysiwygEditorFieldGenerationContributor;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, FieldGenerationContributorInterface>
 */
readonly class FieldGenerationContributorCollection implements IteratorAggregate
{
    public function __construct(
        private HtmlEditorFieldGenerationContributor $htmlEditorFieldGenerationContributor,
        private NumberFieldGenerationContributor $numberFieldGenerationContributor,
        private SingleChoiceFieldGenerationContributor $singleChoiceFieldGenerationContributor,
        private TextFieldGenerationContributor $textFieldGenerationContributor,
        private TextareaFieldGenerationContributor $textareaFieldGenerationContributor,
        private WysiwygEditorFieldGenerationContributor $wysiwygEditorFieldGenerationContributor,
    ) {
    }

    /**
     * @return Traversable<int, FieldGenerationContributorInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator([
            $this->htmlEditorFieldGenerationContributor,
            $this->numberFieldGenerationContributor,
            $this->singleChoiceFieldGenerationContributor,
            $this->textFieldGenerationContributor,
            $this->textareaFieldGenerationContributor,
            $this->wysiwygEditorFieldGenerationContributor,
        ]);
    }
}
