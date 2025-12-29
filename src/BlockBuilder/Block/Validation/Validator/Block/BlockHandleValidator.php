<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Service\BlockTypeService;
use BlockBuilder\Block\Service\ReservedWordsService;
use BlockBuilder\Block\Validation\AbstractValidator;
use BlockBuilder\Block\Validation\ValidationFeedback;
use Concrete\Core\Url\Resolver\Manager\ResolverManager;

class BlockHandleValidator extends AbstractValidator
{
    public function __construct(
        private readonly BlockTypeService $blockTypeService,
        private readonly ReservedWordsService $reservedHandlesService,
        private readonly ResolverManager $resolverManager,
    ) {
    }

    public function validate(array $data): ValidationFeedback
    {
        $errors = [];

        $blockHandle = $data['blockHandle'] ?? '';

        if (!$blockHandle) {
            $errors[] = t('Field "%s" is required (%s).', t('Block handle'), t('Block settings'));
        } else {
            if (mb_strlen($blockHandle) < 3 || mb_strlen($blockHandle) > 50) {
                $errors[] = t('Field "%s" should be between %s and %s characters long (%s).', t('Block handle'), 3, 50, t('Block settings'));
            }

            if (!preg_match('/^[a-z_]+$/', $blockHandle)) {
                $errors[] = t('Field "%s" should only consist of lowercase letters and underscores (%s).', t('Block handle'), t('Block settings'));
            }

            if (mb_substr($blockHandle, 0, 1, 'utf-8') == '_' || mb_substr($blockHandle, -1, 1, 'utf-8') == '_') {
                $errors[] = t('Field "%s" should not start or end with underscore (%s).', t('Block handle'), t('Block settings'));
            }

            if (preg_match('/_{2,}/', $blockHandle)) {
                $errors[] = t('Field "%s" should not consist of two or more consecutive underscores (%s).', t('Block handle'), t('Block settings'));
            }

            if (!empty($data['rebuildBlock'])) {
                // Rebuild block
                if (!$this->blockTypeService->isBlockTypeFolderAlreadyCreated(handle: $blockHandle, searchedFolder: 'application')) {
                    $errors[] = t('Block folder named after chosen handle does not exist. You should build your block instead.');
                } else {
                    if (!$this->blockTypeService->isBlockTypeInstalled($blockHandle)) {
                        $errors[] = t(
                            'You can not rebuild and refresh block that is awaiting installation, %sInstall%s it first. If you tried to rebuild a block by mistake, build it instead.',
                            '<a href="#" class="btn btn-primary btn-sm js-install-block-type" data-handle="' . $blockHandle . '"><i class="fas fa-plus-circle"></i> ',
                            '</a>',
                        );
                    }
                }
            } else {
                // Build block
                if ($this->blockTypeService->isBlockTypeFolderAlreadyCreated(handle: $blockHandle, searchedFolder: 'concrete')) {
                    $errors[] = t('Concrete CMS already uses your chosen handle for one of its blocks. Use different handle. (%s).', t('Block settings'));
                } else {
                    if ($this->blockTypeService->isBlockTypeInstalled($blockHandle)) {
                        $errors[] = t(
                            'Block with that handle is already installed. %sUninstall it%s first and then build block again. Alternatively you can use different handle (%s).',
                            '<a href="#" class="btn btn-danger btn-sm js-uninstall-block-type" data-handle="' . $blockHandle . '"><i class="fas fa-minus-circle"></i> ',
                            '</a>',
                            t('Block settings'),
                        );
                    } else {
                        if ($this->blockTypeService->isBlockTypeFolderAlreadyCreated($blockHandle)) {
                            $errors[] = t(
                                'Block folder named %s already exists. %sPermanently delete that folder%s or use different handle (%s).',
                                '"' . $blockHandle . '"',
                                '<a href="#" class="btn btn-danger btn-sm js-delete-block-type-folder" data-handle="' . $blockHandle . '"><i class="far fa-trash-alt"></i> ',
                                '</a>',
                                t('Block settings'),
                            );
                        }
                    }
                }
            }
            if (!$this->reservedHandlesService->isBlockHandleAllowed($blockHandle)) {
                $errors[] = t('Your "%s" is a forbidden word, use different phrase (%s).', t('Block handle'), t('Block settings'));
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->addError(
                    error: $error,
                    field: 'blockHandle',
                    tab: 'block-settings',
                );
            }
        }

        return $this->getValidationFeedback();
    }
}
