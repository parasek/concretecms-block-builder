<?php defined('C5_EXECUTE') or exit('Access Denied.');

use BlockBuilder\BlockGenerator\Enum\CreateBlockContextEnum;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;

/**
 * @var Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks\BlockBuilder $controller
 * @var BlockBuilder\Environment\Dto\EnvironmentDto $environment
 * @var BlockBuilder\Block\Dto\CreateBlockDto $config
 * @var BlockBuilder\NavigationTab\Enum\NavigationTabEnum[] $navigationTabEnums
 * @var Concrete\Core\Form\Service\Form $form
 * @var BlockBuilder\FieldType\Enum\FieldTypeEnum[] $fieldTypes
 * @var array $fieldsWithError
 * @var string $formActionPath
 * @var array $tabsWithError
 * @var array $blockTypeSets
 * @var array $blockIcons
 * @var array $cacheBlockRecordOptions
 * @var array $cacheBlockOutputOptions
 * @var array $cacheBlockOutputOnPostOptions
 * @var array $cacheBlockOutputForRegisteredUsersOptions
 * @var array $supportSavingNullValuesOptions
 * @var array $ignorePageThemeGridFrameworkContainerOptions
 * @var array $installBlockOptions
 * @var array $entriesAsFirstTabOptions
 * @var array $highlightMultiElementFieldsOptions
 * @var array $dividerOptions
 * @var array $selectFieldTypes
 * @var array $selectFieldListGenerationMethods
 * @var array $selectMultipleFieldTypes
 */
?>

<div class="bb-container"
     id="bbContainer"
     data-csrf-token="<?= h($controller->token->generate('csrf_token')); ?>"
     data-fields-with-errors="<?= h(json_encode($fieldsWithError ?? [])); ?>"
     data-install-block-type-url="<?= app('url/manager')->resolve(['js/install-block-type']); ?>"
     data-uninstall-block-type-url="<?= h(app('url/manager')->resolve(['js/uninstall-block-type'])); ?>"
     data-delete-block-type-folder-url="<?= h(app('url/manager')->resolve(['js/delete-block-type-folder'])); ?>"
     data-confirmation-message="<?= t('Are you sure?'); ?>"
     data-install-block-type-success-message-1="<?= t('Block has been installed.'); ?>"
     data-install-block-type-success-message-2="<?= t('Click Rebuild and refresh block once again.'); ?>"
     data-uninstall-block-type-success-message-1="<?= t('Block has been uninstalled.'); ?>"
     data-uninstall-block-type-success-message-2="<?= t('Click Build your block now! once again.'); ?>"
     data-delete-block-type-folder-success-message-1="<?= t('Block type folder has been deleted.'); ?>"
     data-delete-block-type-folder-success-message-2="<?= t('Click Build your block now! once again.'); ?>"
>
    <div class="ccm-dashboard-header-buttons">
        <a href="<?= h(app('url/manager')->resolve(['dashboard/blocks/block_builder'])); ?>"
           class="btn btn-secondary"
        ><i class="fas fa-plus me-2"></i><?= t('New block'); ?></a>
        <a href="<?= h(app('url/manager')->resolve(['dashboard/blocks/block_builder/configs'])); ?>"
           class="btn btn-secondary"
        >
            <i class="fas fa-upload me-2"></i><?= t('Load config'); ?>
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= t('Close'); ?>"></button>
            <?php foreach ($errors as $errorEntry): ?>
                <div><?= $errorEntry; ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    $infoTable = ['environment' => $environment];
    if (in_array($controller->getAction(), [CreateBlockContextEnum::Config->value, CreateBlockContextEnum::PredefinedConfig->value], true)) {
        $infoTable = array_merge($infoTable, ['config' => $config]);
    }
    View::element('info_table', $infoTable, 'block_builder');
    ?>

    <form method="post" action="<?= h($controller->action($formActionPath)); ?>" enctype="multipart/form-data">
        <?= $controller->token->output('create_block'); ?>

        <?php if (!empty($navigationTabEnums)): ?>

            <ul class="navigation-tabs mb-4" id="navigation-tabs">
                <?php foreach ($navigationTabEnums as $navigationTabEnum): ?>
                    <li>
                        <a href="#"
                           class="navigation-tab-link <?= h(in_array($navigationTabEnum->getHandle(), $tabsWithError) ? 'has-error' : null); ?>"
                           data-tab="<?= h($navigationTabEnum->getHandle()); ?>"
                        ><i class="<?= h($navigationTabEnum->getIcon()); ?> me-2"></i><?= h($navigationTabEnum->getName()); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php foreach ($navigationTabEnums as $k => $navigationTabEnum): ?>
                <div class="ccm-tab-content <?php if (!$k): ?>active<?php endif; ?>"
                     id="ccm-tab-content-<?= h($navigationTabEnum->getHandle()); ?>"
                     style="display: none;"
                >
                    <?php View::element(
                        _file: $navigationTabEnum->getTabContentElementName(),
                        args: [
                            // Common variables used across all tabs
                            'navigationTabEnum' => $navigationTabEnum,
                            'fieldsWithError' => $fieldsWithError,
                            'form' => $form,
                            'config' => $config ?? null,
                            // Block settings
                            'blockTypeSets' => $blockTypeSets,
                            'blockIcons' => $blockIcons,
                            'cacheBlockRecordOptions' => $cacheBlockRecordOptions,
                            'cacheBlockOutputOptions' => $cacheBlockOutputOptions,
                            'cacheBlockOutputOnPostOptions' => $cacheBlockOutputOnPostOptions,
                            'cacheBlockOutputForRegisteredUsersOptions' => $cacheBlockOutputForRegisteredUsersOptions,
                            'supportSavingNullValuesOptions' => $supportSavingNullValuesOptions,
                            'ignorePageThemeGridFrameworkContainerOptions' => $ignorePageThemeGridFrameworkContainerOptions,
                            // Build options
                            'installBlockOptions' => $installBlockOptions,
                            'entriesAsFirstTabOptions' => $entriesAsFirstTabOptions,
                            'highlightMultiElementFieldsOptions' => $highlightMultiElementFieldsOptions,
                            'dividerOptions' => $dividerOptions,
                            // Tab: Basic information / Tab: Repeatable entries
                            'fieldTypes' => $fieldTypes,
                            'fieldTypeContextEnum' => $navigationTabEnum->getFieldTypeContextEnum(),
                            'fields' => match ($navigationTabEnum) {
                                NavigationTabEnum::TabBasicInformation => $config->basic,
                                NavigationTabEnum::TabRepeatableEntries => $config->entries,
                                default => null,
                            },
                        ],
                        _pkgHandle: 'block_builder',
                    ); ?>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

        <hr>

        <p class="footer-required-fields small text-muted">* <?= t('Required fields'); ?></p>

        <div class="ccm-dashboard-form-actions-wrapper">
            <div class="ccm-dashboard-form-actions">
                <?= $form->hidden('sourceAction', $controller->getAction() ?? null); ?>
                <button type="submit"
                        class="btn btn-primary float-end"
                        value="1"
                        name="buildBlock"
                        title="<?= t('Build your block now!'); ?>"
                >
                    <i class="fas fa-hammer me-lg-2"></i><span class="d-none d-lg-inline"><?= t('Build your block now!'); ?></span>
                </button>
                <?php if ($controller->getAction() === CreateBlockContextEnum::Config->value || $this->post('sourceAction') === CreateBlockContextEnum::Config->value): ?>
                    <button type="submit"
                            class="btn btn-secondary float-end me-4"
                            value="1"
                            name="rebuildBlock"
                            title="<?= t('Rebuild and refresh block'); ?>"
                    >
                        <i class="fas fa-sync-alt me-lg-2"></i><span class="d-none d-lg-inline"><?= t('Rebuild and refresh block'); ?></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>

    </form>

    <script type="text/template" class="js-template-no-entries">
        <div class="alert alert-info js-alert mb-4"><?= t('You haven\'t added any fields yet.'); ?></div>
    </script>

    <?php View::element('field_type/field_type_template', [
        'fieldTypes' => $fieldTypes,
        'selectFieldTypes' => $selectFieldTypes,
        'selectFieldListGenerationMethods' => $selectFieldListGenerationMethods,
        'selectMultipleFieldTypes' => $selectMultipleFieldTypes,
    ], 'block_builder'); ?>

</div> <?php // .bb-container ?>
