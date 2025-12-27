<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation;

use Symfony\Component\HttpFoundation\FileBag;

interface ValidatorInterface
{
    public function validate(array $data, ?FileBag $files = null): ValidationFeedback;
}
