<?php defined('C5_EXECUTE') or exit('Access Denied.');

use BlockBuilder\Block\Service\BlockTypeService;
use BlockBuilder\BlockGenerator\Enum\CreateBlockContextEnum;
use BlockBuilder\Environment\EnvironmentService;

/**
 * @var Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks\BlockBuilder\Configs $controller
 * @var BlockBuilder\Environment\Dto\EnvironmentDto $environment
 * @var BlockBuilder\Block\Dto\CreateBlockDto[] $configs
 * @var BlockBuilder\Block\Dto\CreateBlockDto[] $predefinedConfigs
 */

?>
<div class="bb-container-configs">

    <div class="ccm-dashboard-header-buttons">
        <a href="<?= h(app('url/manager')->resolve(['dashboard/blocks/block_builder'])); ?>"
           class="btn btn-secondary"
        ><i class="fas fa-plus me-2"></i><?= t('New block'); ?></a>
        <a href="<?= h(app('url/manager')->resolve(['dashboard/blocks/block_builder'])); ?>"
           class="btn btn-secondary"
        ><i class="fas fa-angle-double-left me-2"></i><?= t('Go back'); ?></a>
    </div>

    <?php View::element('info_table', ['environment' => $environment, 'config' => null], 'block_builder'); ?>

    <div class="bb-block-types-title mb-4 mt-4">
        <?= t('Configuration files found in existing blocks'); ?>
    </div>

    <?php if (!empty($configs)): ?>

        <div class="bb-block-types">

            <?php foreach ($configs as $config): ?>

                <?php
                $bt = app(BlockTypeService::class)->getBlockTypeObject($config->blockHandle);
                ?>

                <div class="bb-block-type d-flex flex-column justify-content-xxl-between flex-xxl-row mb-3 w-100">

                    <div class="bb-block-type-icon mb-3">
                        <img src="<?= h(app(EnvironmentService::class)->getPublicPathToBlockIcon($config->blockHandle)); ?>"
                             class="bb-block-type-icon-image img-fluid"
                             alt="<?= h($config->blockName); ?>"
                        >
                    </div>

                    <div class="bb-block-type-info flex-xxl-grow-1">

                        <div class="bb-block-type-info-heading mb-1">
                            <strong class="bb-block-type-name me-2"><?= h($config->blockName); ?></strong>

                            <?php if (app(BlockTypeService::class)->isBlockTypeInstalled($bt)): ?>
                                <span class="bb-block-type-status badge rounded-pill small bg-success"><?= t('Installed'); ?></span>
                            <?php else: ?>
                                <span class="bb-block-type-status badge rounded-pill small bg-danger"><?= t('Not installed'); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="bb-block-type-handle text-muted mb-1"><?= h($config->blockHandle); ?></div>

                        <div class="bb-block-type-description mb-2 mb-xxl-3">
                            <?php if ($config->blockDescription): ?>
                                <?= h($config->blockDescription); ?>
                            <?php else: ?>
                                <?= t('No description'); ?>
                            <?php endif; ?>
                        </div>

                        <?php if (app(BlockTypeService::class)->isBlockTypeInstalled($bt)): ?>
                            <div class="bb-block-type-usage text-muted small mb-2 d-xxl-flex">
                                <div class="me-xxl-3">
                                    <?= t('Usage count'); ?>:
                                    <?= h($bt->getCount()); ?>
                                </div>
                                <div class="">
                                    <?= t('Usage count on active pages'); ?>:
                                    <a href="<?= $controller->action('search', $bt->getBlockTypeID()); ?>"
                                       target="_blank"
                                    >
                                        <?= h($bt->getCount(ignoreUnapprovedVersions: true)); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="bb-block-type-badges text-muted small mb-3 mb-xxl-0">
                        <span class="badge small bb-block-type-info-badge mb-1">
                           <span class="me-1 text-muted"><?= t('Block Builder'); ?>:</span> <?= h($config->blockBuilderVersion ?? t('No info')); ?>
                        </span>
                            <span class="badge small bb-block-type-info-badge mb-1">
                            <span class="me-1 text-muted"><?= t('Concrete'); ?>:</span> <?= h($config->concreteVersion ?? t('No info')); ?>
                        </span>
                            <span class="badge small bb-block-type-info-badge mb-1">
                            <span class="me-1 text-muted"><?= t('PHP'); ?>:</span> <?= h($config->phpVersion ?? t('No info')); ?>
                        </span>
                            <span class="badge small bb-block-type-info-badge mb-1">
                            <span class="me-1 text-muted"><?= t('Created At'); ?>:</span> <?= h(date('Y-m-d H:i', strtotime($config->createdAt))); ?>
                        </span>
                        </div>

                    </div> <?php // .bb-block-type-info ?>

                    <div class="bb-block-type-actions d-flex flex-column align-items-xxl-end">

                        <?php if (!app(BlockTypeService::class)->isBlockTypeInstalled($bt)): ?>
                            <div class="bb-block-type-action bb-block-type-action-install mb-2">
                                <form action="<?= h(app('url/manager')->resolve(['/dashboard/blocks/block_builder/configs/install/' . $config->blockHandle])); ?>"
                                      method="post"
                                      data-confirm-question="<?= h(t('This will install the %s block type. Are you sure?', $config->blockName)); ?>"
                                      data-block-type-handle="<?= h($config->blockHandle); ?>"
                                >
                                    <?= $controller->token->output('install_block'); ?>
                                    <button class="btn btn-success text-nowrap"
                                            type="submit"
                                    ><i class="fas fa-plus-circle me-2"></i><?= t('Install'); ?></button>
                                </form>
                            </div>

                            <div class="bb-block-type-action bb-block-type-action-delete-folder mb-2">
                                <form action="<?= h(app('url/manager')->resolve(['/dashboard/blocks/block_builder/configs/delete_folder/' . $config->blockHandle])); ?>"
                                      method="post"
                                      data-confirm-question="<?= h(t('This will permanently delete the "%s" folder. This cannot be undone. Are you sure?', DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $config->blockHandle)); ?>"
                                      data-block-type-handle="<?= h($config->blockHandle); ?>"
                                >
                                    <?= $controller->token->output('delete_folder'); ?>
                                    <button class="btn btn-danger text-nowrap"
                                            type="submit"
                                    ><i class="far fa-trash-alt me-2"></i><?= t('Delete folder'); ?></button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <?php if (app(BlockTypeService::class)->isBlockTypeInstalled($bt)): ?>
                            <div class="bb-block-type-action bb-block-type-action-uninstall mb-2">
                                <form action="<?= h(app('url/manager')->resolve(['/dashboard/blocks/block_builder/configs/uninstall/' . $bt->getBlockTypeID()])); ?>"
                                      method="post"
                                      data-confirm-question="<?= h(t('This will remove all instances of the "%s" block type. This cannot be undone. Are you sure?', $config->blockName)); ?>"
                                      data-block-type-id="<?= h($bt->getBlockTypeID()); ?>"
                                >
                                    <?= $controller->token->output('uninstall_block'); ?>
                                    <button class="btn btn-danger text-nowrap"
                                            type="submit"
                                    ><i class="fas fa-minus-circle me-2"></i><?= t('Uninstall'); ?></button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <div class="bb-block-type-action bb-block-type-action-load-config mb-2">
                            <a href="<?= h(app('url/manager')->resolve(['/dashboard/blocks/block_builder/' . CreateBlockContextEnum::Config->value . '/' . $config->blockHandle])); ?>"
                               class="btn btn-primary"
                            >
                                <i class="fas fa-upload me-2"></i><?= t('Load config'); ?>
                            </a>
                        </div>

                    </div> <?php // .bb-block-type-actions ?>

                </div> <?php // .bb-block-type ?>

            <?php endforeach; ?>

        </div> <?php // .bb-block-types ?>

    <?php else: ?>

        <div class="alert alert-info"><?= t('No blocks created by Block Builder were found.'); ?></div>

    <?php endif; ?>

    <div class="bb-block-types-title mb-4 mt-4">
        <?= t('Predefined configuration files'); ?>
    </div>

    <?php if (!empty($predefinedConfigs)): ?>

        <div class="bb-block-types">

            <?php foreach ($predefinedConfigs as $predefinedConfig): ?>

                <?php
                $bt = app(BlockTypeService::class)->getBlockTypeObject($predefinedConfig->blockHandle);
                ?>

                <div class="bb-block-type d-flex flex-column justify-content-xxl-between flex-xxl-row mb-3 w-100">

                    <div class="bb-block-type-icon mb-3">
                        <img src="<?= h(app(EnvironmentService::class)->getPublicPathToDefaultBlockIcon()); ?>"
                             class="bb-block-type-icon-image img-fluid"
                             width="97"
                             height="97"
                             alt="<?= h($predefinedConfig->blockName); ?>"
                        >
                    </div>

                    <div class="bb-block-type-info flex-xxl-grow-1">

                        <div class="bb-block-type-info-heading mb-1">
                            <strong class="bb-block-type-name me-2"><?= h($predefinedConfig->blockName); ?></strong>
                        </div>

                        <div class="bb-block-type-handle text-muted mb-1"><?= h($predefinedConfig->blockHandle); ?></div>

                        <div class="bb-block-type-description mb-2 mb-xxl-3">
                            <?php if ($predefinedConfig->blockDescription): ?>
                                <?= h($predefinedConfig->blockDescription); ?>
                            <?php else: ?>
                                <?= t('No description'); ?>
                            <?php endif; ?>
                        </div>

                        <div class="bb-block-type-badges text-muted small mb-3 mb-xxl-0">
                        <span class="badge small block-type-info-badge mb-1">
                           <span class="me-1 text-muted"><?= t('Block Builder'); ?>:</span> <?= h($predefinedConfig->blockBuilderVersion ?? t('No info')); ?>
                        </span>
                            <span class="badge small bb-block-type-info-badge mb-1">
                            <span class="me-1 text-muted"><?= t('Concrete'); ?>:</span> <?= h($predefinedConfig->concreteVersion ?? t('No info')); ?>
                        </span>
                            <span class="badge small bb-block-type-info-badge mb-1">
                            <span class="me-1 text-muted"><?= t('PHP'); ?>:</span> <?= h($predefinedConfig->phpVersion ?? t('No info')); ?>
                        </span>
                            <span class="badge small bb-block-type-info-badge mb-1">
                            <span class="me-1 text-muted"><?= t('Created At'); ?>:</span> <?= h(date('Y-m-d H:i', strtotime($predefinedConfig->createdAt))); ?>
                        </span>
                        </div>

                    </div> <?php // .bb-block-type-info ?>

                    <div class="bb-block-type-actions d-flex flex-column align-items-xxl-end">

                        <div class="bb-block-type-action bb-block-type-action-load-config mb-2">
                            <a href="<?= h(app('url/manager')->resolve(['/dashboard/blocks/block_builder/' . CreateBlockContextEnum::PredefinedConfig->value . '/' . $predefinedConfig->blockHandle])); ?>"
                               class="btn btn-primary"
                            >
                                <i class="fas fa-upload me-2"></i><?= t('Load config'); ?>
                            </a>
                        </div>

                    </div> <?php // .bb-block-type-actions ?>

                </div> <?php // .bb-block-type ?>

            <?php endforeach; ?>

        </div> <?php // .bb-block-types ?>

    <?php endif; ?>

</div> <?php // .bb-container-configs ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-confirm-question]').forEach(function(element) {
            element.addEventListener('submit', function(e) {
                if (!confirm(element.getAttribute('data-confirm-question'))) {
                    e.preventDefault();
                }
            });
        });
    });
</script>
