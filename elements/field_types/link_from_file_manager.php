<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<hr class="field-entry-hr">

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowEndingField]"
           id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowEndingField]"
           value="1"
        <?= '<% if (parseInt(linkFromFileManagerShowEndingField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowEndingField]"
           class="form-check-label"
    ><?= t('Show the "Custom string at the end of URL" field'); ?></label>
</div>

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTextField]"
           id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTextField]"
           value="1"
    <?= '<% if (parseInt(linkFromFileManagerShowTextField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTextField]"
           class="form-check-label"
    ><?= t('Show the "Text" field'); ?> <span class="text-muted">- <?= t('The text inside the %s tag, e.g., %s', h('<a>'), h('<a href="#">Example text</a>')); ?></span></label>
</div>

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTitleField]"
           id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTitleField]"
           value="1"
    <?= '<% if (parseInt(linkFromFileManagerShowTitleField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowTitleField]"
           class="form-check-label"
    ><?= t('Show the "Title" field'); ?> <span class="text-muted">- <?= t('The value of the title attribute, e.g., %s', h('<a href="#" title="Example title"></a>')); ?></span></label>
</div>

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNewWindowField]"
           id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNewWindowField]"
           value="1"
    <?= '<% if (parseInt(linkFromFileManagerShowNewWindowField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNewWindowField]"
           class="form-check-label"
    ><?= t('Show the "Open in new window" field'); ?></label>
</div>

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNoFollowField]"
           id="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNoFollowField]"
           value="1"
    <?= '<% if (parseInt(linkFromFileManagerShowNoFollowField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][linkFromFileManagerShowNoFollowField]"
           class="form-check-label"
    ><?= t('Show the "Add nofollow attribute" field'); ?></label>
</div>
