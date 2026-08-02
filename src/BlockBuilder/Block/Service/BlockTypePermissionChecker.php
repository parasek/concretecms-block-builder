<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use Concrete\Core\Permission\Key\Key as Permissions;
use Concrete\Core\User\User;

readonly class BlockTypePermissionChecker
{
    public function __construct(private User $user)
    {
    }

    public function getInstallationErrorMessage(): ?string
    {
        return Permissions::getByHandle('install_packages')->validate()
            ? null
            : t('You do not have permission to install custom block types or add-ons.');
    }

    public function getRemovalErrorMessage(): ?string
    {
        if (!$this->user->isSuperUser()) {
            return t('Only the super user may remove block types.');
        }

        return Permissions::getByHandle('uninstall_packages')->validate()
            ? null
            : t('You do not have permission to uninstall packages.');
    }
}
