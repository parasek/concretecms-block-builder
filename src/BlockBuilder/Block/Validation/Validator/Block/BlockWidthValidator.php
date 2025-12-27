<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\ValidatorInterface;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Symfony\Component\HttpFoundation\FileBag;

class BlockWidthValidator implements ValidatorInterface
{
    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $errors = [];

        $blockWidth = $data['blockWidth'] ?? null;

        if (!$blockWidth) {
            $errors[] = t('The field "%s" is required (%s).', t('Block width'), NavigationTabEnum::BlockSettings->getName());
        } else {
            if (!is_string($blockWidth) || !ctype_digit($blockWidth) || $blockWidth < 300 || $blockWidth > 2000) {
                $errors[] = t('The field "%s" must be a number between %s and %s (%s).', t('Block width'), 300, 2000, NavigationTabEnum::BlockSettings->getName());
            }
        }

        return new ValidationFeedback(
            errors: $errors,
            fieldsWithError: $errors === [] ? [] : ['blockWidth'],
            tabsWithError: $errors === [] ? [] : [NavigationTabEnum::BlockSettings->getHandle()],
        );
    }
}
