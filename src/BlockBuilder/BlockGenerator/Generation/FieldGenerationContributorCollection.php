<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation;

use ArrayIterator;
use BlockBuilder\FieldType\Type\ExternalLink\Generation\ExternalLinkFieldGenerationContributor;
use BlockBuilder\FieldType\Type\FlexLink\Generation\FlexLinkFieldGenerationContributor;
use BlockBuilder\FieldType\Type\HtmlEditor\Generation\HtmlEditorFieldGenerationContributor;
use BlockBuilder\FieldType\Type\LinkFromFileManager\Generation\LinkFromFileManagerFieldGenerationContributor;
use BlockBuilder\FieldType\Type\LinkFromSitemap\Generation\LinkFromSitemapFieldGenerationContributor;
use BlockBuilder\FieldType\Type\MultipleChoice\Generation\MultipleChoiceFieldGenerationContributor;
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
        private ExternalLinkFieldGenerationContributor $externalLinkFieldGenerationContributor,
        private FlexLinkFieldGenerationContributor $flexLinkFieldGenerationContributor,
        private HtmlEditorFieldGenerationContributor $htmlEditorFieldGenerationContributor,
        private LinkFromFileManagerFieldGenerationContributor $linkFromFileManagerFieldGenerationContributor,
        private LinkFromSitemapFieldGenerationContributor $linkFromSitemapFieldGenerationContributor,
        private MultipleChoiceFieldGenerationContributor $multipleChoiceFieldGenerationContributor,
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
            $this->externalLinkFieldGenerationContributor,
            $this->flexLinkFieldGenerationContributor,
            $this->htmlEditorFieldGenerationContributor,
            $this->linkFromFileManagerFieldGenerationContributor,
            $this->linkFromSitemapFieldGenerationContributor,
            $this->multipleChoiceFieldGenerationContributor,
            $this->numberFieldGenerationContributor,
            $this->singleChoiceFieldGenerationContributor,
            $this->textFieldGenerationContributor,
            $this->textareaFieldGenerationContributor,
            $this->wysiwygEditorFieldGenerationContributor,
        ]);
    }
}
