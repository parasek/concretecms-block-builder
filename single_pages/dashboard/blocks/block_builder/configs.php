<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<div class="ccm-dashboard-header-buttons">
    <a href="<?php echo $app->make('url/manager')->resolve(['dashboard/blocks/block_builder']); ?>" class="btn btn-secondary"><i class="fas fa-angle-double-left"></i> <?php echo t('Go back'); ?></a>
</div>


<?php if (is_array($blockTypes) and count($blockTypes)): ?>

    <div class="mb-4">
        <p><?php echo t('Current package version:'); ?> <?php echo $pkgVersion ?></p>
        <p><?php echo t('Configuration files found in existing blocks:'); ?></p>
    </div>

    <?php foreach ($blockTypes as $blockType): ?>

        <a href="<?php echo $app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/config/block/' . $blockType['handle']]); ?>"
           class="block-type">
            <?php echo h($blockType['name']); ?> / <?php echo h($blockType['handle']); ?>

            <?php if ($blockType['description']): ?>
                <br/>
                <small class="text-muted"><?php echo h($blockType['description']); ?></small>
            <?php endif; ?>

            <?php if ($blockType['version']): ?>
                <br/>
                <small class="text-muted"><?php echo t('Version'); ?>: <?php echo h($blockType['version']); ?></small>
            <?php endif; ?>
        </a>

    <?php endforeach; ?>

<?php else: ?>

    <div class="alert alert-info"><?php echo t('No blocks created by Block Builder have been found.'); ?></div>

<?php endif; ?>

<div class="mb-4 mt-4">
    <p><?php echo t('Predefined configuration files for testing:'); ?></p>
</div>

<?php foreach ($predefinedConfigs as $predefinedConfig): ?>

    <a href="<?php echo $app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/config/predefined/' . $predefinedConfig['handle']]); ?>"
       class="block-type"
    >
        <?php echo h($predefinedConfig['name']); ?> / <?php echo h($predefinedConfig['handle']); ?>

        <br/>

        <?php if ($predefinedConfig['description']): ?>
            <small class="text-muted"><?php echo h($predefinedConfig['description']); ?></small>
        <?php endif; ?>

        <br/>

        <?php if ($predefinedConfig['version']): ?>
            <small class="text-muted"><?php echo t('Version'); ?>: <?php echo h($predefinedConfig['version']); ?></small>
        <?php endif; ?>
    </a>

<?php endforeach; ?>
