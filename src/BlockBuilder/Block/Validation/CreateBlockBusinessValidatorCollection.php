<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation;

use BlockBuilder\Block\Validation\Validator\Block\BlockHandleValidator;
use BlockBuilder\Block\Validation\Validator\Block\BlockHeightValidator;
use BlockBuilder\Block\Validation\Validator\Block\BlockIconValidator;
use BlockBuilder\Block\Validation\Validator\Block\BlockNameValidator;
use BlockBuilder\Block\Validation\Validator\Block\BlockNumericValuesValidator;
use BlockBuilder\Block\Validation\Validator\Block\BlockOptionValuesValidator;
use BlockBuilder\Block\Validation\Validator\Block\BlockWidthValidator;
use BlockBuilder\Block\Validation\Validator\Block\ExcludedFromRemovalValidator;
use BlockBuilder\Block\Validation\Validator\Block\LabelsValidator;
use BlockBuilder\Block\Validation\Validator\FieldType\FieldTypeValidator;
use Symfony\Component\HttpFoundation\FileBag;

readonly class CreateBlockBusinessValidatorCollection
{
    public function __construct(
        private BlockNameValidator $blockNameValidator,
        private BlockHandleValidator $blockHandleValidator,
        private BlockWidthValidator $blockWidthValidator,
        private BlockHeightValidator $blockHeightValidator,
        private BlockNumericValuesValidator $blockNumericValuesValidator,
        private BlockOptionValuesValidator $blockOptionValuesValidator,
        private BlockIconValidator $blockIconValidator,
        private ExcludedFromRemovalValidator $excludedFromRemovalValidator,
        private LabelsValidator $labelsValidator,
        private FieldTypeValidator $fieldTypeValidator,
    ) {
    }

    public function validate(array $data, FileBag $files): ValidationFeedback
    {
        $feedback = new ValidationFeedbackBuilder();
        foreach ($this->getValidators() as $validator) {
            $feedback->addFeedback($validator->validate($data, $files));
        }

        return $feedback->build();
    }

    /**
     * Block-level checks run first and field-level checks run last.
     *
     * @return list<ValidatorInterface>
     */
    private function getValidators(): array
    {
        return [
            $this->blockNameValidator,
            $this->blockHandleValidator,
            $this->blockWidthValidator,
            $this->blockHeightValidator,
            $this->blockNumericValuesValidator,
            $this->blockOptionValuesValidator,
            $this->blockIconValidator,
            $this->excludedFromRemovalValidator,
            $this->labelsValidator,

            $this->fieldTypeValidator,
        ];
    }
}
