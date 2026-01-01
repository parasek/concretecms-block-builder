<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\AbstractValidator;
use BlockBuilder\Block\Validation\ValidationFeedback;
use Concrete\Core\Validation\CSRF\Token;
use Symfony\Component\HttpFoundation\FileBag;

class CsrfValidator extends AbstractValidator
{
    public function __construct(
        private readonly Token $token,
    ) {
    }

    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $errors = [];

        if (!$this->token->validate('create_block')) {
            $errors[] = t('Invalid CSRF token. Please refresh and try again.');
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addError(
                    error: $error,
                    field: null,
                    tab: null,
                );
            }
        }

        return $this->getValidationFeedback();
    }
}
