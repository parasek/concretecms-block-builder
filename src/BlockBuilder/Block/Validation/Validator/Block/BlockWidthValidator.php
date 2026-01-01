<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\AbstractValidator;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Symfony\Component\HttpFoundation\FileBag;

class BlockWidthValidator extends AbstractValidator
{
    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $errors = [];

        if (!$data['blockWidth']) {
            $errors[] = t('Field "%s" is required (%s).', t('Block width'), t('Block settings'));
        } else {
            if (!ctype_digit($data['blockWidth']) || $data['blockWidth'] < 300 || $data['blockWidth'] > 2000) {
                $errors[] = t('Field "%s" should be a number between %s and %s (%s).', t('Block width'), 300, 2000, t('Block settings'));
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addError(
                    error: $error,
                    field: 'blockWidth',
                    tab: NavigationTabEnum::BlockSettings->getHandle(),
                );
            }
        }

        return $this->getValidationFeedback();
    }
}
