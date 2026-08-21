<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var Concrete\Package\BlockBuilder\Controller\SinglePage\Dashboard\Blocks\BlockBuilder\Configs $controller
 * @var BlockBuilder\Environment\Dto\EnvironmentDto $environment
 * @var BlockBuilder\Block\View\BlockConfigListItem[] $configItems
 * @var BlockBuilder\Block\View\BlockConfigListItem[] $predefinedConfigItems
 * @var string[] $configLoadingErrors
 * @var string $newBlockUrl
 */

?>
<div class="bb-app bb-app-configs">

    <?php foreach ($configLoadingErrors as $configLoadingError): ?>
        <div class="alert alert-danger"><?= h($configLoadingError); ?></div>
    <?php endforeach; ?>

    <div class="ccm-dashboard-header-buttons">
        <a href="<?= h($newBlockUrl); ?>"
           class="btn btn-secondary"
        ><i class="fas fa-plus me-2"></i><?= t('New block'); ?></a>
        <a href="<?= h($newBlockUrl); ?>"
           class="btn btn-secondary"
        ><i class="fas fa-angle-double-left me-2"></i><?= t('Go back'); ?></a>
    </div>

    <?php View::element('info_table', ['environment' => $environment, 'config' => null], 'block_builder'); ?>

    <div class="bb-block-types-title mb-4 mt-4">
        <?= t('Configuration files found in existing blocks'); ?>
    </div>

    <?php if (!empty($configItems)): ?>

        <div class="bb-block-types">

            <?php foreach ($configItems as $item): ?>

                <?php
                $config = $item->config;
                ?>

                <div class="bb-block-type d-flex flex-column justify-content-xxl-between flex-xxl-row mb-3 w-100">

                    <div class="bb-block-type-icon mb-3">
                        <img src="<?= h($item->iconPath); ?>"
                             class="bb-block-type-icon-image img-fluid"
                             alt="<?= h($config->blockName); ?>"
                        >
                    </div>

                    <div class="bb-block-type-info flex-xxl-grow-1">

                        <div class="bb-block-type-info-heading mb-1">
                            <strong class="bb-block-type-name me-2"><?= h($config->blockName); ?></strong>

                            <?php if ($item->installed): ?>
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

                        <?php if ($item->installed): ?>
                            <div class="bb-block-type-usage text-muted small mb-2 d-xxl-flex">
                                <div class="me-xxl-3">
                                    <?= t('Total instances'); ?>:
                                    <?= h($item->usageCount); ?>
                                </div>
                                <div class="">
                                    <?= t('Instances on active pages'); ?>:
                                    <a href="<?= h($item->usageUrl); ?>"
                                       target="_blank"
                                    >
                                        <?= h($item->activeUsageCount); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="bb-block-type-badges text-muted small mb-3 mb-xxl-0">
                        <span class="badge small bb-block-type-info-badge mb-1">
                           <span class="me-1 text-muted"><?= t('Block Builder'); ?>:</span> <?= h($config->blockBuilderVersion ?? t('Not available')); ?>
                        </span>
                            <span class="badge small bb-block-type-info-badge mb-1">
                            <span class="me-1 text-muted"><?= t('Concrete CMS'); ?>:</span> <?= h($config->concreteVersion ?? t('Not available')); ?>
                        </span>
                            <span class="badge small bb-block-type-info-badge mb-1">
                            <span class="me-1 text-muted"><?= t('PHP'); ?>:</span> <?= h($config->phpVersion ?? t('Not available')); ?>
                        </span>
                            <span class="badge small bb-block-type-info-badge mb-1">
                            <span class="me-1 text-muted"><?= t('Created'); ?>:</span>
                            <?= h($config->createdAt ? date('Y-m-d H:i', strtotime($config->createdAt)) : t('Not available')); ?>
                        </span>
                        </div>

                    </div> <?php // .bb-block-type-info ?>

                    <div class="bb-block-type-actions d-flex flex-column align-items-xxl-end">

                        <?php if (!$item->installed): ?>
                            <div class="bb-block-type-action bb-block-type-action-install mb-2">
                                <?php $installConfirmationQuestion = t('This will install the "%s" block type. Are you sure?', $config->blockName); ?>
                                <form action="<?= h($item->installUrl); ?>"
                                      method="post"
                                      data-confirm-question="<?= h($installConfirmationQuestion); ?>"
                                      data-block-type-handle="<?= h($config->blockHandle); ?>"
                                >
                                    <?= $controller->token->output('install_block'); ?>
                                    <button class="btn btn-success text-nowrap"
                                            type="submit"
                                    ><i class="fas fa-plus-circle me-2"></i><?= t('Install'); ?></button>
                                </form>
                            </div>

                            <div class="bb-block-type-action bb-block-type-action-delete-folder mb-2">
                                <?php $deleteConfirmationQuestion = t('This will permanently delete the block folder "%s". This cannot be undone. Are you sure?', $config->blockHandle); ?>
                                <form action="<?= h($item->deleteDirectoryUrl); ?>"
                                      method="post"
                                      data-confirm-question="<?= h($deleteConfirmationQuestion); ?>"
                                      data-block-type-handle="<?= h($config->blockHandle); ?>"
                                >
                                    <?= $controller->token->output('delete_folder'); ?>
                                    <button class="btn btn-danger text-nowrap"
                                            type="submit"
                                    ><i class="far fa-trash-alt me-2"></i><?= t('Delete folder'); ?></button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <?php if ($item->installed): ?>
                            <div class="bb-block-type-action bb-block-type-action-uninstall mb-2">
                                <?php $uninstallConfirmationQuestion = t('This will remove all instances of the "%s" block type. This cannot be undone. Are you sure?', $config->blockName); ?>
                                <form action="<?= h($item->uninstallUrl); ?>"
                                      method="post"
                                      data-confirm-question="<?= h($uninstallConfirmationQuestion); ?>"
                                      data-block-type-id="<?= h($item->blockTypeId); ?>"
                                >
                                    <?= $controller->token->output('uninstall_block'); ?>
                                    <button class="btn btn-danger text-nowrap"
                                            type="submit"
                                    ><i class="fas fa-minus-circle me-2"></i><?= t('Uninstall'); ?></button>
                                </form>
                            </div>
                        <?php endif; ?>

                        <div class="bb-block-type-action bb-block-type-action-load-config mb-2">
                            <a href="<?= h($item->loadUrl); ?>"
                               class="btn btn-primary"
                            >
                                <i class="fas fa-upload me-2"></i><?= t('Load configuration'); ?>
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

    <?php if (!empty($predefinedConfigItems)): ?>

        <div class="bb-block-types">

            <?php foreach ($predefinedConfigItems as $item): ?>

                <?php
                $predefinedConfig = $item->config;
                ?>

                <div class="bb-block-type d-flex flex-column justify-content-xxl-between flex-xxl-row mb-3 w-100">

                    <div class="bb-block-type-icon mb-3">
                        <img src="<?= h($item->iconPath); ?>"
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
                        <span class="badge small bb-block-type-info-badge mb-1">
                           <span class="me-1 text-muted"><?= t('Block Builder'); ?>:</span> <?= h($predefinedConfig->blockBuilderVersion ?? t('Not available')); ?>
                        </span>
                            <span class="badge small bb-block-type-info-badge mb-1">
                            <span class="me-1 text-muted"><?= t('Concrete CMS'); ?>:</span> <?= h($predefinedConfig->concreteVersion ?? t('Not available')); ?>
                        </span>
                            <span class="badge small bb-block-type-info-badge mb-1">
                            <span class="me-1 text-muted"><?= t('PHP'); ?>:</span> <?= h($predefinedConfig->phpVersion ?? t('Not available')); ?>
                        </span>
                            <span class="badge small bb-block-type-info-badge mb-1">
                            <span class="me-1 text-muted"><?= t('Created'); ?>:</span>
                            <?= h($predefinedConfig->createdAt ? date('Y-m-d H:i', strtotime($predefinedConfig->createdAt)) : t('Not available')); ?>
                        </span>
                        </div>

                    </div> <?php // .bb-block-type-info ?>

                    <div class="bb-block-type-actions d-flex flex-column align-items-xxl-end">

                        <div class="bb-block-type-action bb-block-type-action-load-config mb-2">
                            <a href="<?= h($item->loadUrl); ?>"
                               class="btn btn-primary"
                            >
                                <i class="fas fa-upload me-2"></i><?= t('Load configuration'); ?>
                            </a>
                        </div>

                    </div> <?php // .bb-block-type-actions ?>

                </div> <?php // .bb-block-type ?>

            <?php endforeach; ?>

        </div> <?php // .bb-block-types ?>

    <?php endif; ?>

</div> <?php // .bb-app.bb-app-configs ?>

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
