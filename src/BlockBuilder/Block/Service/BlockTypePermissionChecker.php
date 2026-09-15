<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Service;

use Concrete\Core\Application\Application;
use Concrete\Core\Permission\Key\Key as Permissions;
use Concrete\Core\User\User;

readonly class BlockTypePermissionChecker
{
    public function __construct(
        private User $user,
        private Application $application,
    ) {
    }

    public function getInstallationErrorMessage(): ?string
    {
        // Concrete console commands are authorized by shell access, without a Dashboard session.
        if (Application::isRunThroughCommandLineInterface() && $this->application->bound('console')) {
            return null;
        }

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
