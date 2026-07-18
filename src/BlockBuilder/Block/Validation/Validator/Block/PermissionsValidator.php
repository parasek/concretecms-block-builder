<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\ValidatorInterface;
use BlockBuilder\Block\Validation\ValidationFeedback;
use Concrete\Core\Permission\Key\Key as Permissions;
use Symfony\Component\HttpFoundation\FileBag;

class PermissionsValidator implements ValidatorInterface
{
    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $errors = [];

        $key = Permissions::getByHandle('install_packages');
        if (!$key->validate()) {
            $errors[] = t('You do not have permission to install custom block types or add-ons.');
        }

        return new ValidationFeedback(errors: $errors);
    }
}
