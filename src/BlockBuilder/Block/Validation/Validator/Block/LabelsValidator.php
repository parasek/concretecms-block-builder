<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\AbstractValidator;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Symfony\Component\HttpFoundation\FileBag;

class LabelsValidator extends AbstractValidator
{
    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        if (!$data['addAtTheTopLabel'] && !$data['addAtTheBottomLabel']) {
            $this->addError(
                error: t('At least one label for buttons ("Add at the top" or "Add at the bottom") is required (%s).', NavigationTabEnum::Labels->getName()),
                field: 'addAtTheTopLabel',
                tab: NavigationTabEnum::BlockSettings->getHandle(),
            );
        }

        if (!empty($data['basic']) && empty($data['basicLabel'])) {
            $this->addError(
                error: t('Label for "%s" is required (%s).', t('Basic information'), NavigationTabEnum::Labels->getName()),
                field: 'basicLabel',
                tab: NavigationTabEnum::BlockSettings->getHandle(),
            );
        }

        if (!empty($data['entries']) && empty($data['entriesLabel'])) {
            $this->addError(
                error: t('Label for "%s" is required (%s).', t('Entries'), NavigationTabEnum::Labels->getName()),
                field: 'entriesLabel',
                tab: NavigationTabEnum::BlockSettings->getHandle(),
            );
        }

        return $this->getValidationFeedback();
    }
}
