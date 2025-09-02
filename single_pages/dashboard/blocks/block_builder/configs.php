<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<div class="ccm-dashboard-header-buttons">
    <a href="<?= $app->make('url/manager')->resolve(['dashboard/blocks/block_builder']); ?>"
       class="btn btn-secondary"><i class="fas fa-angle-double-left"></i> <?= t('Go back'); ?></a>
</div>

<div class="mb-4">
    <span
        class="small badge rounded-pill bg-secondary"><?= t('Block Builder Version'); ?></span> <?= $systemInfo['block_builder_version'] ?? t('No info'); ?>
    <span
        class="small badge rounded-pill bg-secondary ms-2"><?= t('Concrete Version'); ?></span> <?= $systemInfo['concrete_version'] ?? t('No info'); ?>
    <span
        class="small badge rounded-pill bg-secondary ms-2"><?= t('PHP Version'); ?></span> <?= $systemInfo['php_version'] ?? t('No info'); ?>
</div>

<div class="mb-4">
    <p><?= t('Configuration files found in existing blocks:'); ?></p>
</div>

<?php if (is_array($blockTypes) and count($blockTypes)): ?>

    <?php foreach ($blockTypes as $blockType): ?>

        <div class="block-type">
            <a href="<?= $app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/config/block/' . $blockType['handle']]); ?>"
               class="block-type-build">
                <strong class="me-2"><?= h($blockType['name']); ?></strong>
                <span class="small badge rounded-pill bg-secondary"><?= h($blockType['handle']); ?></span>

                <?php if ($blockType['description']): ?>
                    <br/>
                    <small class="text-muted"><?= h($blockType['description']); ?></small>
                <?php endif; ?>

                <?php if ($blockType['version']): ?>
                    <br/>
                    <small class="text-muted"><?= t('Version'); ?>: <?= h($blockType['version']); ?></small>
                <?php endif; ?>
            </a>
            <a href="<?= $app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/config/refresh/' . $blockType['handle']]); ?>"
               class="block-type-refresh"
            >
                <i class="fas fa-hammer me-2"></i> <?= t('Rebuild and refresh'); ?><br/>
                <?= t('(experimental)'); ?>
            </a>
        </div>

    <?php endforeach; ?>

<?php else: ?>

    <div class="alert alert-info"><?= t('No blocks created by Block Builder have been found.'); ?></div>

<?php endif; ?>

<div class="mb-4 mt-4">
    <p><?= t('Predefined configuration files for testing:'); ?></p>
</div>

<?php foreach ($predefinedConfigs as $predefinedConfig): ?>

    <div class="block-type">
        <a href="<?= $app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/config/predefined/' . $predefinedConfig['handle']]); ?>"
           class="block-type-build block-type-build-single"
        >
            <strong class="me-2"><?= h($predefinedConfig['name']); ?></strong>
            <span class="small badge rounded-pill bg-secondary"><?= h($predefinedConfig['handle']); ?></span>

            <br/>

            <?php if ($predefinedConfig['description']): ?>
                <small class="text-muted"><?= h($predefinedConfig['description']); ?></small>
            <?php endif; ?>

            <br/>

            <?php if ($predefinedConfig['version']): ?>
                <small class="text-muted"><?= t('Block Builder Version'); ?>: <?= h($predefinedConfig['version']); ?></small>
            <?php endif; ?>
        </a>
    </div>

<?php endforeach; ?>
