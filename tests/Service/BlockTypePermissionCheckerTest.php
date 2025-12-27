<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Service;

use BlockBuilder\Block\Service\BlockTypePermissionChecker;
use Concrete\Core\Application\Application;
use Concrete\Core\Support\Facade\Facade;
use Concrete\Core\User\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application as ConsoleApplication;

final class BlockTypePermissionCheckerTest extends TestCase
{
    public function testConsoleInstallationDoesNotRequireADashboardSession(): void
    {
        $application = new Application();
        $application->instance('console', new ConsoleApplication());
        $user = $this->createMock(User::class);
        $user->expects(self::never())->method('isSuperUser');

        self::assertNull((new BlockTypePermissionChecker($user, $application))->getInstallationErrorMessage());
    }

    /** @dataProvider dashboardPermissionProvider */
    public function testWithoutConsoleTheDashboardPermissionIsEnforced(bool $allowed): void
    {
        $previousApplication = Facade::getFacadeApplication();
        $application = new Application();
        // Supply a cached permission key without connecting to a CMS database.
        $permission = new class($allowed) {
            public int $validationCount = 0;

            public function __construct(private bool $allowed)
            {
            }

            public function validate(): bool
            {
                ++$this->validationCount;

                return $this->allowed;
            }
        };
        $application->instance('cache/request', new class($permission) {
            public function __construct(private object $permission)
            {
            }

            public function isEnabled(): bool
            {
                return true;
            }

            public function getItem(string $key): object
            {
                TestCase::assertSame('permission_keys', $key);

                return new class($this->permission) {
                    public function __construct(private object $permission)
                    {
                    }

                    public function isMiss(): bool
                    {
                        return false;
                    }

                    public function get(): array
                    {
                        return ['install_packages' => $this->permission];
                    }
                };
            }
        });
        // Keep the translation service from the test bootstrap available for denial messages.
        $application->instance(
            \Concrete\Core\Localization\Localization::class,
            $previousApplication->make(\Concrete\Core\Localization\Localization::class),
        );
        Facade::setFacadeApplication($application);
        try {
            $checker = new BlockTypePermissionChecker($this->createMock(User::class), $application);
            $error = $checker->getInstallationErrorMessage();
            if ($allowed) {
                self::assertNull($error);
            } else {
                self::assertSame('You do not have permission to install custom block types or add-ons.', $error);
            }
            self::assertSame(1, $permission->validationCount);
        } finally {
            Facade::setFacadeApplication($previousApplication);
        }
    }

    public static function dashboardPermissionProvider(): array
    {
        return ['allowed' => [true], 'denied' => [false]];
    }

    public function testConsoleDoesNotGrantRemovalPermission(): void
    {
        $application = new Application();
        $application->instance('console', new ConsoleApplication());
        $user = $this->createMock(User::class);
        $user->method('isSuperUser')->willReturn(false);

        self::assertSame(
            'Only the super user may remove block types.',
            (new BlockTypePermissionChecker($user, $application))->getRemovalErrorMessage(),
        );
    }
}
