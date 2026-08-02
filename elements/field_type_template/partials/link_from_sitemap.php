<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%-context%>[<%-counter%>][showEndingField]"
               id="<%-context%>[<%-counter%>][showEndingField]"
               value="1"
            <?= '<% if (showEndingField === true || showEndingField === 1 || showEndingField === \'1\') { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%-context%>[<%-counter%>][showEndingField]"
               class="form-check-label"
        ><?= t('Show the "Custom string at the end of URL" field'); ?></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%-context%>[<%-counter%>][showTextField]"
               id="<%-context%>[<%-counter%>][showTextField]"
               value="1"
            <?= '<% if (showTextField === true || showTextField === 1 || showTextField === \'1\') { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%-context%>[<%-counter%>][showTextField]"
               class="form-check-label"
        ><?= t('Show the "Text" field'); ?>
            <span class="text-muted">- <?= t('The text inside the %s tag, e.g., %s', h('<a>'), h('<a href="#">Example text</a>')); ?></span></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%-context%>[<%-counter%>][showTitleField]"
               id="<%-context%>[<%-counter%>][showTitleField]"
               value="1"
            <?= '<% if (showTitleField === true || showTitleField === 1 || showTitleField === \'1\') { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%-context%>[<%-counter%>][showTitleField]"
               class="form-check-label"
        ><?= t('Show the "Title" field'); ?>
            <span class="text-muted">- <?= t('The value of the title attribute, e.g., %s', h('<a href="#" title="Example title"></a>')); ?></span></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%-context%>[<%-counter%>][showNewWindowField]"
               id="<%-context%>[<%-counter%>][showNewWindowField]"
               value="1"
            <?= '<% if (showNewWindowField === true || showNewWindowField === 1 || showNewWindowField === \'1\') { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%-context%>[<%-counter%>][showNewWindowField]"
               class="form-check-label"
        ><?= t('Show the "Open in new window" field'); ?></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%-context%>[<%-counter%>][showNoFollowField]"
               id="<%-context%>[<%-counter%>][showNoFollowField]"
               value="1"
            <?= '<% if (showNoFollowField === true || showNoFollowField === 1 || showNoFollowField === \'1\') { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%-context%>[<%-counter%>][showNoFollowField]"
               class="form-check-label"
        ><?= t('Show the "Add nofollow attribute" field'); ?></label>
    </div>

</script>
