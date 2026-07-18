<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\ReservedWord\ReservedHandleChecker;
use BlockBuilder\Block\Service\BlockDirectoryLocator;
use BlockBuilder\Block\Service\BlockTypeLocator;
use BlockBuilder\Block\Validation\ValidatorInterface;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Symfony\Component\HttpFoundation\FileBag;

class BlockHandleValidator implements ValidatorInterface
{
    public function __construct(
        private readonly BlockTypeLocator $blockTypeLocator,
        private readonly BlockDirectoryLocator $blockDirectoryLocator,
        private readonly ReservedHandleChecker $reservedHandleChecker,
    ) {
    }

    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $errors = [];
        $blockHandle = is_string($data['blockHandle'] ?? null)
            ? $data['blockHandle']
            : '';

        if ($blockHandle === '') {
            $errors[] = t('The field "%s" is required (%s).', t('Block handle'), NavigationTabEnum::BlockSettings->getName());

            return $this->createFeedback($errors);
        }

        if (mb_strlen($blockHandle) < 3 || mb_strlen($blockHandle) > 50) {
            $errors[] = t('The field "%s" should be between %s and %s characters long (%s).', t('Block handle'), 3, 50, NavigationTabEnum::BlockSettings->getName());
        }
        if (preg_match('/^[a-z_]+$/', $blockHandle) !== 1) {
            $errors[] = t('The field "%s" should consist only of lowercase letters and underscores (%s).', t('Block handle'), NavigationTabEnum::BlockSettings->getName());
        }
        if (str_starts_with($blockHandle, '_') || str_ends_with($blockHandle, '_')) {
            $errors[] = t('The field "%s" should not start or end with an underscore (%s).', t('Block handle'), NavigationTabEnum::BlockSettings->getName());
        }
        if (str_contains($blockHandle, '__')) {
            $errors[] = t('The field "%s" should not contain two or more consecutive underscores (%s).', t('Block handle'), NavigationTabEnum::BlockSettings->getName());
        }

        if ($errors !== []) {
            return $this->createFeedback($errors);
        }

        if (!$this->reservedHandleChecker->isBlockHandleAllowed($blockHandle)) {
            $errors[] = t('Your "%s" is a forbidden word. Use a different phrase (%s).', t('Block handle'), NavigationTabEnum::BlockSettings->getName());

            return $this->createFeedback($errors);
        }

        if (!empty($data['rebuildBlock'])) {
            if (!$this->blockDirectoryLocator->hasCollision(handle: $blockHandle, searchedFolder: 'application')) {
                $errors[] = t('A block folder named after the chosen handle does not exist. Build the block first.');
            } elseif (!$this->blockTypeLocator->isInstalled($blockHandle)) {
                $errors[] = t('You cannot rebuild a block that is awaiting installation. Install it from the configuration list first.')
                    . PHP_EOL
                    . t('If you selected rebuild by mistake, build the block instead.');
            }

            return $this->createFeedback($errors);
        }

        if ($this->blockDirectoryLocator->hasCollision(handle: $blockHandle, searchedFolder: 'concrete')) {
            $errors[] = t(
                'The handle is already used by a Concrete CMS core block. Choose a different handle (%s).',
                NavigationTabEnum::BlockSettings->getName(),
            );
        } elseif ($this->blockTypeLocator->isInstalled($blockHandle)) {
            $errors[] = t('A block type with this handle is already installed. Uninstall it from the configuration list before building it again.')
                . PHP_EOL
                . t('Alternatively, choose a different handle (%s).', NavigationTabEnum::BlockSettings->getName());
        } elseif ($this->blockDirectoryLocator->hasCollision(handle: $blockHandle, searchedFolder: 'application')) {
            $errors[] = t(
                'A block folder named "%s" already exists. Delete it from the configuration list or choose a different handle (%s).',
                $blockHandle,
                NavigationTabEnum::BlockSettings->getName(),
            );
        }

        return $this->createFeedback($errors);
    }

    private function createFeedback(array $errors): ValidationFeedback
    {
        return new ValidationFeedback(
            errors: $errors,
            fieldsWithError: $errors === [] ? [] : ['blockHandle'],
            tabsWithError: $errors === [] ? [] : [NavigationTabEnum::BlockSettings->getHandle()],
        );
    }
}
