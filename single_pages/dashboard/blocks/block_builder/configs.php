<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<div class="ccm-dashboard-header-buttons">
    <a href="<?php echo $app->make('url/manager')->resolve(['dashboard/blocks/block_builder']); ?>"
       class="btn btn-secondary"><i class="fas fa-angle-double-left"></i> <?php echo t('Go back'); ?></a>
</div>

<div class="mb-4">
    <span
        class="small badge rounded-pill bg-secondary"><?php echo t('Block Builder Version'); ?></span> <?php echo $systemInfo['block_builder_version'] ?? t('No info'); ?>
    <span
        class="small badge rounded-pill bg-secondary ms-2"><?php echo t('Concrete Version'); ?></span> <?php echo $systemInfo['concrete_version'] ?? t('No info'); ?>
    <span
        class="small badge rounded-pill bg-secondary ms-2"><?php echo t('PHP Version'); ?></span> <?php echo $systemInfo['php_version'] ?? t('No info'); ?>
</div>

<div class="mb-4">
    <p><?php echo t('Configuration files found in existing blocks:'); ?></p>
</div>

<?php if (is_array($blockTypes) and count($blockTypes)): ?>

    <?php foreach ($blockTypes as $blockType): ?>

        <div class="block-type">
            <a href="<?php echo $app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/config/block/' . $blockType['handle']]); ?>"
               class="block-type-build">
                <strong class="me-2"><?php echo h($blockType['name']); ?></strong>
                <span class="small badge rounded-pill bg-secondary"><?php echo h($blockType['handle']); ?></span>

                <?php if ($blockType['description']): ?>
                    <br/>
                    <small class="text-muted"><?php echo h($blockType['description']); ?></small>
                <?php endif; ?>

                <?php if ($blockType['version']): ?>
                    <br/>
                    <small class="text-muted"><?php echo t('Version'); ?>: <?php echo h($blockType['version']); ?></small>
                <?php endif; ?>
            </a>
            <a href="<?php echo $app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/config/refresh/' . $blockType['handle']]); ?>"
               class="block-type-refresh"
            >
                <i class="fas fa-hammer me-2"></i> <?php echo t('Rebuild and refresh'); ?><br/>
                <?php echo t('(experimental)'); ?>
            </a>
        </div>

    <?php endforeach; ?>

<?php else: ?>

    <div class="alert alert-info"><?php echo t('No blocks created by Block Builder have been found.'); ?></div>

<?php endif; ?>

<div class="mb-4 mt-4">
    <p><?php echo t('Predefined configuration files for testing:'); ?></p>
</div>

<?php foreach ($predefinedConfigs as $predefinedConfig): ?>

    <div class="block-type">
        <a href="<?php echo $app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/config/predefined/' . $predefinedConfig['handle']]); ?>"
           class="block-type-build block-type-build-single"
        >
            <strong class="me-2"><?php echo h($predefinedConfig['name']); ?></strong>
            <span class="small badge rounded-pill bg-secondary"><?php echo h($predefinedConfig['handle']); ?></span>

            <br/>

            <?php if ($predefinedConfig['description']): ?>
                <small class="text-muted"><?php echo h($predefinedConfig['description']); ?></small>
            <?php endif; ?>

            <br/>

            <?php if ($predefinedConfig['version']): ?>
                <small class="text-muted"><?php echo t('Block Builder Version'); ?>: <?php echo h($predefinedConfig['version']); ?></small>
            <?php endif; ?>
        </a>
    </div>

<?php endforeach; ?>
