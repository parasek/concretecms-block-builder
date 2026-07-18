<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\ValidatorInterface;
use BlockBuilder\Block\Validation\IntegerValueValidator;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\Block\Validation\ValidationFeedbackBuilder;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Symfony\Component\HttpFoundation\FileBag;

class BlockNumericValuesValidator implements ValidatorInterface
{
    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $feedback = new ValidationFeedbackBuilder();

        if (!IntegerValueValidator::isInRange($data['maxNumberOfEntries'] ?? null, 0)) {
            $feedback->addError(
                error: t('The field "%s" should be a non-negative whole number (%s).', t('Max. number of entries'), NavigationTabEnum::BuildOptions->getName()),
                field: 'maxNumberOfEntries',
                tab: NavigationTabEnum::BuildOptions->getHandle(),
            );
        }

        if (!IntegerValueValidator::isInRange($data['cacheBlockOutputLifetime'] ?? null, 0)) {
            $feedback->addError(
                error: t('The field "%s" should be a non-negative whole number (%s).', t('Cache block output lifetime'), NavigationTabEnum::BlockSettings->getName()),
                field: 'cacheBlockOutputLifetime',
                tab: NavigationTabEnum::BlockSettings->getHandle(),
            );
        }

        return $feedback->build();
    }
}
