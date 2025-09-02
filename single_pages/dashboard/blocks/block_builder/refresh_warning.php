<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<div class="ccm-dashboard-header-buttons">
    <a href="<?= $app->make('url/manager')->resolve(['dashboard/blocks/block_builder']); ?>"
       class="btn btn-secondary"><i class="fas fa-angle-double-left"></i> <?= t('Go back'); ?></a>
</div>
<h5 class="mb-4"><?= t('Rebuilding and refreshing blocks'); ?></h5>

<p>
    <?= t('This is an experimental feature, but it should work without much problems in most common situations.'); ?>
    <br/>
    <?= t('By default, when block is already installed, Block Builder will require you to uninstall it and confirm to remove folder.'); ?>
    <br/>
    <?= t('Albeit all data will be lost, this workflow guarantees that your custom block will be installed as new/fresh block.'); ?>
    <br/>
    <?= t('Rebuilding/refreshing will skip all those steps (and importantly it will not delete existing data) - but this comes with some caveats.'); ?>
</p>

<p><?= t('Before you rebuild and refresh block:'); ?></p>
<ul>
    <li><?= t('Backup your database in case something went wrong.'); ?></li>
    <li><?= t('Backup your files in case something went wrong.'); ?></li>
</ul>
<p><?= t('What can you do:'); ?></p>
<ul>
    <li><?= t('You can add new Field Types.'); ?></li>
    <li><?= t('You can remove existing Field Types.'); ?></li>
    <li><?= t('You can modify all options including Labels/Help texts etc.'); ?></li>
</ul>

<p><?= t('What you shouldn\'t do:'); ?></p>
<ul>
    <li>
        <?= t('You should not remove Field type and then add different Field type with the same handle.'); ?>
        <br/><?= t('Block Builder is not designed to handle those situations.'); ?>
        <br/><?= t('Data already saved in database won\'t be magically converted by this package or Concrete CMS.'); ?>
        <br/><?= t('So most of the time it is not a good idea.'); ?>
    </li>
</ul>

<p><?= t('Other things to keep in mind:'); ?></p>
<ul>
    <li>
        <?= t('Changing handle of Field type will create new column in database, so data from old column/field will be deleted/lost.'); ?></li>
    <li>
        <?= t('It is a good idea to check all occurrences of rebuild/refreshed block on your site. I have tried to thoroughly test it, but some edge cases could slip through testing process.'); ?>
    </li>
</ul>
