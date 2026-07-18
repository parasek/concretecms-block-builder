<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\ValidatorInterface;
use BlockBuilder\Block\Validation\ValidationFeedback;
use Concrete\Core\Validation\CSRF\Token;
use Symfony\Component\HttpFoundation\FileBag;

readonly class CsrfValidator implements ValidatorInterface
{
    public function __construct(
        private Token $token,
    ) {
    }

    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $errors = [];

        if (!$this->token->validate('create_block')) {
            $errors[] = t('Invalid CSRF token. Please refresh and try again.');
        }

        return new ValidationFeedback(errors: $errors);
    }
}
