<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<hr class="field-entry-hr">

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][externalLinkShowEndingField]"
           id="<%=groupHandle%>[<%=counter%>][externalLinkShowEndingField]"
           value="1"
        <?= '<% if (parseInt(externalLinkShowEndingField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowEndingField]"
           class="form-check-label"
    ><?= t('Show the "Custom string at the end of URL" field'); ?></label>
</div>

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][externalLinkShowTextField]"
           id="<%=groupHandle%>[<%=counter%>][externalLinkShowTextField]"
           value="1"
        <?= '<% if (parseInt(externalLinkShowTextField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowTextField]"
           class="form-check-label"
    ><?= t('Show the "Text" field'); ?> <span class="text-muted">- <?= t('The text inside the %s tag, e.g., %s', h('<a>'), h('<a href="#">Example text</a>')); ?></span></label>
</div>

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][externalLinkShowTitleField]"
           id="<%=groupHandle%>[<%=counter%>][externalLinkShowTitleField]"
           value="1"
        <?= '<% if (parseInt(externalLinkShowTitleField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowTitleField]"
           class="form-check-label"
    ><?= t('Show the "Title" field'); ?> <span class="text-muted">- <?= t('The value of the title attribute, e.g., %s', h('<a href="#" title="Example title"></a>')); ?></span></label>
</div>

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][externalLinkShowNewWindowField]"
           id="<%=groupHandle%>[<%=counter%>][externalLinkShowNewWindowField]"
           value="1"
        <?= '<% if (parseInt(externalLinkShowNewWindowField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowNewWindowField]"
           class="form-check-label"
    ><?= t('Show the "Open in new window" field'); ?></label>
</div>

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][externalLinkShowNoFollowField]"
           id="<%=groupHandle%>[<%=counter%>][externalLinkShowNoFollowField]"
           value="1"
        <?= '<% if (parseInt(externalLinkShowNoFollowField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][externalLinkShowNoFollowField]"
           class="form-check-label"
    ><?= t('Show the "Add nofollow attribute" field'); ?></label>
</div>
