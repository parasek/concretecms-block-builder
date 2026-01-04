<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Service\BlockTypeService;
use BlockBuilder\Block\Service\ReservedWordsService;
use BlockBuilder\Block\Validation\AbstractValidator;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Symfony\Component\HttpFoundation\FileBag;

class BlockHandleValidator extends AbstractValidator
{
    public function __construct(
        private readonly BlockTypeService $blockTypeService,
        private readonly ReservedWordsService $reservedHandlesService,
    ) {
    }

    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $errors = [];

        $blockHandle = $data['blockHandle'] ?? '';

        if (!$blockHandle) {
            $errors[] = t('The field "%s" is required (%s).', t('Block handle'), NavigationTabEnum::BlockSettings->getName());
        } else {
            if (mb_strlen($blockHandle) < 3 || mb_strlen($blockHandle) > 50) {
                $errors[] = t('The field "%s" should be between %s and %s characters long (%s).', t('Block handle'), 3, 50, NavigationTabEnum::BlockSettings->getName());
            }

            if (!preg_match('/^[a-z_]+$/', $blockHandle)) {
                $errors[] = t('The field "%s" should consist only of lowercase letters and underscores (%s).', t('Block handle'), NavigationTabEnum::BlockSettings->getName());
            }

            if (mb_substr($blockHandle, 0, 1, 'utf-8') == '_' || mb_substr($blockHandle, -1, 1, 'utf-8') == '_') {
                $errors[] = t('The field "%s" should not start or end with an underscore (%s).', t('Block handle'), NavigationTabEnum::BlockSettings->getName());
            }

            if (preg_match('/_{2,}/', $blockHandle)) {
                $errors[] = t('The field "%s" should not contain two or more consecutive underscores (%s).', t('Block handle'), NavigationTabEnum::BlockSettings->getName());
            }

            if (!empty($data['rebuildBlock'])) {
                // Rebuild block
                if (!$this->blockTypeService->isBlockTypeFolderAlreadyCreated(handle: $blockHandle, searchedFolder: 'application')) {
                    $errors[] = t('A block folder named after the chosen handle does not exist. You should build your block first.');
                } else {
                    if (!$this->blockTypeService->isBlockTypeInstalled($blockHandle)) {
                        $errors[] = t(
                                'You cannot rebuild a block that is awaiting installation. %sInstall it%s first.',
                                '<a href="#" class="text-success text-nowrap" data-install-block-type data-handle="' . $blockHandle . '"><i class="fas fa-plus-circle"></i> ',
                                '</a>',
                            ) . PHP_EOL .
                            t('If you tried to rebuild the block by mistake, build it instead.');
                    }
                }
            } else {
                // Build block
                if ($this->blockTypeService->isBlockTypeFolderAlreadyCreated(handle: $blockHandle, searchedFolder: 'concrete')) {
                    $errors[] = t('The handle you chose is already used by another Concrete CMS block. Please choose a different handle. (%s).', NavigationTabEnum::BlockSettings->getName());
                } else {
                    if ($this->blockTypeService->isBlockTypeInstalled($blockHandle)) {
                        $errors[] = t('Are you sure you want to build the block? Maybe you meant to rebuild it?') . PHP_EOL .
                            t('A block type with that handle is already installed. %sUninstall it%s first, then build the block again.',
                                '<a href="#" class="text-danger text-nowrap" data-uninstall-block-type data-handle="' . $blockHandle . '"><i class="fas fa-minus-circle"></i> ',
                                '</a>',
                            ) . PHP_EOL .
                            t('Alternatively, you can use a different handle (%s).', NavigationTabEnum::BlockSettings->getName());
                    } else {
                        if ($this->blockTypeService->isBlockTypeFolderAlreadyCreated($blockHandle)) {
                            $errors[] = t(
                                'A folder named %s already exists. %sPermanently delete that folder%s or choose a different handle (%s).',
                                '"' . $blockHandle . '"',
                                '<a href="#" class="text-danger text-nowrap" data-delete-block-type-folder data-handle="' . $blockHandle . '"><i class="far fa-trash-alt"></i> ',
                                '</a>',
                                NavigationTabEnum::BlockSettings->getName(),
                            );
                        }
                    }
                }
            }
            if (!$this->reservedHandlesService->isBlockHandleAllowed($blockHandle)) {
                $errors[] = t('Your "%s" is a forbidden word. Use a different phrase (%s).', t('Block handle'), NavigationTabEnum::BlockSettings->getName());
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addError(
                    error: $error,
                    field: 'blockHandle',
                    tab: NavigationTabEnum::BlockSettings->getHandle(),
                );
            }
        }

        return $this->getValidationFeedback();
    }
}
