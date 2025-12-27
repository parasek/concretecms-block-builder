<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<p class="small text-muted">
    <?= t('Custom PHP code that will run in the on_start() method of controller.php.'); ?>
    <br>
    <?= t('Use this to list items from Express or another custom source.'); ?>
    <br>
    <?= t('Be careful when inserting custom code; invalid syntax can lead to errors.'); ?>
    <br><br>
    <strong><?= t('Example code for a field in the "Basic information" tab'); ?>:</strong>
    <br>
    <?= t('If this field\'s handle is "category", name its options variable $category_options.'); ?>
    <br>
    <?= t('Use %s spaces for indentation.', 8); ?>
    <br>
    <code class="bb-code-block">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$list = new \Concrete\Core\Page\PageList();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$pages = $list->getResults();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;foreach ($pages as $page) {
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$category_options[$page->getCollectionID()] = $page->getCollectionName();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}
    </code>
    <br>
    <strong><?= t('Example code for a field in the "Repeatable entries" tab'); ?>:</strong>
    <br>
    <?= t('If this field\'s handle is "category", name its options variable $entry_category_options.'); ?>
    <br>
    <?= t('Use %s spaces for indentation.', 8); ?>
    <br>
    <code class="bb-code-block">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$list = new \Concrete\Core\Page\PageList();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$pages = $list->getResults();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;foreach ($pages as $page) {
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$entry_category_options[$page->getCollectionID()] = $page->getCollectionName();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}
    </code>
</p>
