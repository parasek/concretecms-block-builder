<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<p class="small text-muted">
    <?php echo t('Custom PHP code that will be run in on_start() method in controller.php.'); ?>
    <br>
    <?php echo t('This is especially useful when you want to list items from Express or any custom source.'); ?>
    <br>
    <?php echo t('Basic entries: If Handle of this field is "category", then all option variables should be named like "$category_options".'); ?>
    <code class="bb-code-block">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$list = new \Concrete\Core\Page\PageList();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$pages = $list->getResults();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;foreach ($pages as $page) {
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$category_options[$page->getCollectionID()] = $page->getCollectionName();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}
    </code>
    <?php echo t('Repeatable entries: If Handle of this field is "category", then all option variables should be named like "$entry_category_options     ".'); ?>
    <code class="bb-code-block">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$list = new \Concrete\Core\Page\PageList();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$pages = $list->getResults();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;foreach ($pages as $page) {
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$entry_category_options[$page->getCollectionID()] = $page->getCollectionName();
        <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;}
    </code>
    <?php echo t('Be careful when inserting custom code. Invalid syntax can lead to errors.'); ?>
</p>
