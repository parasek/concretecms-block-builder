<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\ValidatorInterface;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Symfony\Component\HttpFoundation\FileBag;

class BlockNameValidator implements ValidatorInterface
{
    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $errors = [];

        if (empty($data['blockName'])) {
            $errors[] = t('The field "%s" is required (%s).', t('Block name'), NavigationTabEnum::BlockSettings->getName());
        } elseif (mb_strlen($data['blockName']) < 3 || mb_strlen($data['blockName']) > 100) {
            $errors[] = t('The field "%s" should be between %s and %s characters long (%s).', t('Block name'), 3, 100, NavigationTabEnum::BlockSettings->getName());
        }

        return new ValidationFeedback(
            errors: $errors,
            fieldsWithError: $errors === [] ? [] : ['blockName'],
            tabsWithError: $errors === [] ? [] : [NavigationTabEnum::BlockSettings->getHandle()],
        );
    }
}
