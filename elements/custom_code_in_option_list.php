<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<p class="small text-muted">
    <?= t('Custom PHP code that will be executed in the on_start() method in controller.php.'); ?>
    <br>
    <?= t('This is especially useful when you want to list items from Express or any other custom source.'); ?>
    <br>
    <?= t('Be careful when inserting custom code; invalid syntax can lead to errors.'); ?>
    <br><br>
    <strong><?= t('Example code for a field in the "Basic Information" tab'); ?>:</strong>
    <br>
    <?= t('If the handle of this field is "category", option variables should be named like $category_options.'); ?>
    <br>
    <?= t('Use %s spaces for indentation.', 8); ?>
    <code class="bb-code-block">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$list = new \Concrete\Core\Page\PageList();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$pages = $list->getResults();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;foreach ($pages as $page) {
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$category_options[$page->getCollectionID()] = $page->getCollectionName();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}
    </code>
    <br>
    <strong><?= t('Example code for a field in the "Repeatable Entries" tab'); ?>:</strong>
    <br>
    <?= t('If the handle of this field is "category", option variables should be named like $entry_category_options.'); ?>
    <br>
    <?= t('Use %s spaces for indentation.', 12); ?>
    <code class="bb-code-block">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$list = new \Concrete\Core\Page\PageList();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$pages = $list->getResults();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;foreach ($pages as $page) {
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$entry_category_options[$page->getCollectionID()] = $page->getCollectionName();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}
    </code>
</p>
