<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<div class="ccm-dashboard-header-buttons">
    <a href="<?php echo $app->make('url/manager')->resolve(['dashboard/blocks/block_builder']); ?>" class="btn btn-default"><i class="fa fa-angle-double-left"></i> <?php echo t('Go back'); ?></a>
</div>


<?php if (is_array($blockTypes) AND count($blockTypes)): ?>

    <div class="form-group">
        <p><?php echo t('We have found configuration file in blocks listed below:'); ?></p>
    </div>

    <?php foreach ($blockTypes as $blockType): ?>

        <a href="<?php echo $app->make('url/manager')->resolve(['/dashboard/blocks/block_builder/config/'.$blockType['handle']]); ?>" class="block-type"><?php echo $blockType['name']; ?></a>

    <?php endforeach; ?>

<?php else: ?>

    <div class="alert alert-info"><?php echo t('No blocks created by Block Builder have been found.'); ?></div>

<?php endif; ?>
