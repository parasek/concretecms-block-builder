<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\ValidatorInterface;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\Block\Validation\ValidationFeedbackBuilder;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Symfony\Component\HttpFoundation\FileBag;

class LabelsValidator implements ValidatorInterface
{
    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $feedback = new ValidationFeedbackBuilder();

        if (!$data['addAtTheTopLabel'] && !$data['addAtTheBottomLabel']) {
            $feedback->addError(
                error: t('At least one label for the buttons ("Add at the top" or "Add at the bottom") is required (%s).', NavigationTabEnum::Labels->getName()),
                field: 'addAtTheTopLabel',
                tab: NavigationTabEnum::Labels->getHandle(),
            );
        }

        if (!empty($data['basic']) && empty($data['basicLabel'])) {
            $feedback->addError(
                error: t('The label for "%s" is required (%s).', t('Basic information'), NavigationTabEnum::Labels->getName()),
                field: 'basicLabel',
                tab: NavigationTabEnum::Labels->getHandle(),
            );
        }

        if (!empty($data['entries']) && empty($data['entriesLabel'])) {
            $feedback->addError(
                error: t('Label for "%s" is required (%s).', t('Entries'), NavigationTabEnum::Labels->getName()),
                field: 'entriesLabel',
                tab: NavigationTabEnum::Labels->getHandle(),
            );
        }

        return $feedback->build();
    }
}
