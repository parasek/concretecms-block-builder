<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\AbstractValidator;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Symfony\Component\HttpFoundation\FileBag;

class BlockHeightValidator extends AbstractValidator
{
    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $errors = [];

        if (!$data['blockHeight']) {
            $errors[] = t('The field "%s" is required (%s).', t('Block height'), NavigationTabEnum::BlockSettings->getName());
        } else {
            if (!ctype_digit($data['blockHeight']) || $data['blockHeight'] < 300 || $data['blockHeight'] > 2000) {
                $errors[] = t('The field "%s" should be a number between %s and %s (%s).', t('Block width'), 300, 2000, NavigationTabEnum::BlockSettings->getName());
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
