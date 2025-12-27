<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\AbstractValidator;
use BlockBuilder\Block\Validation\ValidationFeedback;

class BlockNameValidator extends AbstractValidator
{
    public function validate(array $data): ValidationFeedback
    {
        $errors = [];

        if (empty($data['blockName'])) {
            $errors[] = t('Field "%s" is required (%s).', t('Block name'), t('Block settings'));
        } elseif (mb_strlen($data['blockName']) < 3 || mb_strlen($data['blockName']) > 100) {
            $errors[] = t('Field "%s" should be between %s and %s characters long (%s).', t('Block name'), 3, 100, t('Block settings'));
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addError(
                    error: $error,
                    field: 'blockName',
                    tab: 'block-settings',
                );
            }
        }

        return $this->getValidationFeedback();
    }
}
