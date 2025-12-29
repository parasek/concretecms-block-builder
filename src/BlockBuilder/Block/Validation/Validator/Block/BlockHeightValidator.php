<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\AbstractValidator;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;

class BlockHeightValidator extends AbstractValidator
{
    public function validate(array $data): ValidationFeedback
    {
        $errors = [];

        if (!$data['blockHeight']) {
            $errors[] = t('Field "%s" is required (%s).', t('Block height'), t('Block settings'));
        } else {
            if (!ctype_digit($data['blockHeight']) || $data['blockHeight'] < 300 || $data['blockHeight'] > 2000) {
                $errors[] = t('Field "%s" should be a number between %s and %s (%s).', t('Block width'), 300, 2000, t('Block settings'));
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addError(
                    error: $error,
                    field: 'blockHeight',
                    tab: NavigationTabEnum::BlockSettings->getHandle(),
                );
            }
        }

        return $this->getValidationFeedback();
    }
}
