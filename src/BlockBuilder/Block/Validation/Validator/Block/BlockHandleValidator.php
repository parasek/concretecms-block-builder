<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\ReservedWord\ReservedHandleChecker;
use BlockBuilder\Block\Service\BlockDirectoryLocator;
use BlockBuilder\Block\Service\BlockOwnershipChecker;
use BlockBuilder\Block\Service\BlockTypeLocator;
use BlockBuilder\Block\Validation\BlockHandleFormat;
use BlockBuilder\Block\Validation\ValidatorInterface;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Symfony\Component\HttpFoundation\FileBag;

readonly class BlockHandleValidator implements ValidatorInterface
{
    public function __construct(
        private BlockTypeLocator $blockTypeLocator,
        private BlockDirectoryLocator $blockDirectoryLocator,
        private BlockOwnershipChecker $blockOwnershipChecker,
        private ReservedHandleChecker $reservedHandleChecker,
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

        if (!BlockHandleFormat::isLengthValid($blockHandle)) {
            $errors[] = t(
                'The field "%s" should be between %s and %s characters long (%s).',
                t('Block handle'),
                BlockHandleFormat::MIN_LENGTH,
                BlockHandleFormat::MAX_LENGTH,
                NavigationTabEnum::BlockSettings->getName(),
            );
        }
        if (!BlockHandleFormat::containsOnlyAllowedCharacters($blockHandle)) {
            $errors[] = t('The field "%s" should consist only of lowercase letters and underscores (%s).', t('Block handle'), NavigationTabEnum::BlockSettings->getName());
        }
        if (!BlockHandleFormat::hasValidBoundaryCharacters($blockHandle)) {
            $errors[] = t('The field "%s" should not start or end with an underscore (%s).', t('Block handle'), NavigationTabEnum::BlockSettings->getName());
        }
        if (BlockHandleFormat::containsConsecutiveUnderscores($blockHandle)) {
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
            $rebuildSourceHandle = $data['rebuildSourceHandle'] ?? null;
            if (!is_string($rebuildSourceHandle) || $rebuildSourceHandle !== $blockHandle) {
                $errors[] = t('A block can only be rebuilt from its own loaded configuration.');
            } elseif (!$this->blockOwnershipChecker->isOwnedApplicationBlock($blockHandle)) {
                $errors[] = t('The selected block does not have a valid Block Builder configuration. Build it as a new block instead.');
            } elseif (!$this->blockTypeLocator->isInstalled($blockHandle)) {
                $errors[] = t('You cannot rebuild a block that is awaiting installation. Install it from the config list first.');
            }

            return $this->createFeedback($errors);
        }

        if ($this->blockDirectoryLocator->hasCoreBlockCollision($blockHandle)) {
            $errors[] = t(
                'The handle is already used by a Concrete CMS core block. Choose a different handle (%s).',
                NavigationTabEnum::BlockSettings->getName(),
            );
        } elseif ($this->blockTypeLocator->isInstalled($blockHandle)) {
            $errors[] = t('A block type with this handle is already installed. Uninstall it from the configuration list before building it again. Alternatively, choose a different handle (%s).', NavigationTabEnum::BlockSettings->getName());
        } elseif ($this->blockDirectoryLocator->hasApplicationBlockCollision($blockHandle)) {
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
