<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var Concrete\Core\Application\Application $app
 * @var BlockBuilder\Environment\Dto\EnvironmentDto $environment
 * @var BlockBuilder\Block\Dto\CreateBlockDto[] $configs
 * @var BlockBuilder\Block\Dto\CreateBlockDto[] $predefinedConfigs
 */

$u = $app->make(Concrete\Core\User\User::class);
?>

<div class="ccm-dashboard-header-buttons">
    <a href="<?= h($app->make('url/manager')->resolve(['dashboard/blocks/block_builder'])); ?>"
       class="btn btn-secondary"
    ><i class="fas fa-angle-double-left"></i> <?= t('Go back'); ?></a>
</div>

<?php View::element('environment', ['environment' => $environment], 'block_builder'); ?>

<div class="mb-4">
    <p><?= t('Configuration files found in existing blocks:'); ?></p>
</div>

<?php if (!empty($configs)): ?>

    <div class="block-types">

        <?php foreach ($configs as $config): ?>

            <div class="block-type">
                <div class="block-type-icon">
                    <img src="/application/blocks/all_fields/icon.png" alt="">
                </div>
                <div class="block-type-info">
                    <strong class="me-2"><?= h($config->blockName); ?></strong>
                    <span class="small badge rounded-pill bg-primary"><?= h($config->blockHandle); ?></span>

                    <?php if ($config->blockDescription): ?>
                        <br>
                        <small class="text-muted"><?= h($config->blockDescription); ?></small>
                    <?php endif; ?>
                    <br>
                    <small class="text-muted">
                        <span class="small badge rounded-pill info-item-config">
                            <?= t('Block Builder Version'); ?>: <?= h($config->blockBuilderVersion); ?>
                        </span>
                        <span class="small badge rounded-pill info-item-config">
                            <?= t('Concrete Version'); ?>: <?= h($config->concreteVersion); ?>
                        </span>
                        <span class="small badge rounded-pill info-item-config">
                            <?= t('PHP Version'); ?>: <?= h($config->phpVersion); ?>
                        </span>
                        <span class="small badge rounded-pill info-item-config">
                            <?= t('Created At'); ?>: <?= h($config->createdAt); ?>
                        </span>
                    </small>
                </div>
                <div class="block-type-usage">
                    <div class=""><?= t('Usage Count'); ?>: XXXX</div>
                    <div class=""><?= t('Usage Count on Active Pages'); ?>: XXXX</div>
                </div>
                <div class="block-type-status">
                    <div class=""><?= t('Status'); ?></div>
                    <div class="">
                        <?php if ($app->make(\BlockBuilder\Block\Service\BlockTypeService::class)->isBlockTypeInstalled($config->blockHandle)): ?>
                            <?= t('Installed'); ?>
                        <?php else: ?>
                            <?= t('Not installed'); ?>
                            <br>xXxx ISNTALL BUTTON
                        <?php endif; ?>
                    </div>
                </div>
                <div class="block-type-actions">
                    <?php if ($app->make(\BlockBuilder\Block\Service\BlockTypeService::class)->isBlockTypeInstalled($config->blockHandle)): ?>
                        <?php if ($u->isSuperUser()): ?>
                            <form action="<?= h($app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/configs/uninstall/' . $app->make(\BlockBuilder\Block\Service\BlockTypeService::class)->getBlockTypeId($config->blockHandle)])); ?>"
                                  method="post"
                                  data-uninstall
                                  data-confirm-question="<?= h(t('This will remove all instances of the %s block type. This cannot be undone. Are you sure?', $config->blockName)); ?>"
                                  data-block-type-id="<?= h($app->make(\BlockBuilder\Block\Service\BlockTypeService::class)->getBlockTypeId($config->blockHandle)); ?>"
                            >
                                <?= $this->controller->token->output('uninstall_block'); ?>
                                <button class="btn btn-danger"
                                        type="submit"
                                ><?= t('Uninstall'); ?></button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($u->isSuperUser()): ?>
                            <form action="<?= h($app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/configs/delete_folder/' . $config->blockHandle])); ?>"
                                  method="post"
                                  data-delete-folder
                                  data-confirm-question="<?= h(t('This will permanently delete "%s" folder. This cannot be undone. Are you sure?', DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $config->blockHandle)); ?>"
                                  data-block-type-handle="<?= h($config->blockHandle); ?>"
                            >
                                <?= $this->controller->token->output('delete_folder'); ?>
                                <button class="btn btn-danger"
                                        type="submit"
                                ><i class="far fa-trash-alt"></i> <?= t('Delete folder'); ?></button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <a href="<?= h($app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/config/' . $config->blockHandle])); ?>"
                   class="btn btn-primary"
                >
                    <i class="fas fa-hammer me-2"></i> <?= t('Load config'); ?>
                </a>
            </div>

        <?php endforeach; ?>

    </div>

<?php else: ?>

    <div class="alert alert-info"><?= t('No blocks created by Block Builder have been found.'); ?></div>

<?php endif; ?>

<div class="mb-4 mt-4">
    <p><?= t('Predefined configuration files for testing:'); ?></p>
</div>

<?php if (!empty($predefinedConfigs)): ?>

    <div class="block-types">

        <?php foreach ($predefinedConfigs as $predefinedConfig): ?>

            <div class="block-type">
                <a href="<?= h($app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/predefined_config/' . $predefinedConfig->blockHandle])); ?>"
                   class="block-type-build block-type-build-single"
                >
                    <strong class="me-2"><?= h($predefinedConfig->blockName); ?></strong>
                    <span class="small badge rounded-pill bg-primary"><?= h($predefinedConfig->blockHandle); ?></span>

                    <br>

                    <?php if ($predefinedConfig->blockDescription): ?>
                        <small class="text-muted"><?= h($predefinedConfig->blockDescription); ?></small>
                    <?php endif; ?>

                    <br>

                    <?php if ($predefinedConfig->blockBuilderVersion): ?>
                        <small class="text-muted">
                            <span class="small badge rounded-pill info-item-config">
                                <?= t('Block Builder Version'); ?>: <?= h($predefinedConfig->blockBuilderVersion ?? t('No info')); ?>
                            </span>
                            <span class="small badge rounded-pill info-item-config">
                                <?= t('Concrete Version'); ?>: <?= h($predefinedConfig->concreteVersion ?? t('No info')); ?>
                            </span>
                            <span class="small badge rounded-pill info-item-config">
                                <?= t('PHP Version'); ?>: <?= h($predefinedConfig->phpVersion ?? t('No info')); ?>
                            </span>
                            <span class="small badge rounded-pill info-item-config">
                                <?= t('Created at'); ?>: <?= h($predefinedConfig->createdAt ?? t('No info')); ?>
                            </span>
                        </small>
                    <?php endif; ?>
                </a>
            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-uninstall]').forEach(function(element) {
            element.addEventListener('submit', function(e) {
                if (!confirm(element.getAttribute('data-confirm-question'))) {
                    e.preventDefault();
                }
            });
        });
        document.querySelectorAll('[data-delete-folder]').forEach(function(element) {
            element.addEventListener('submit', function(e) {
                if (!confirm(element.getAttribute('data-confirm-question'))) {
                    e.preventDefault();
                }
            });
        });
    });
</script>
