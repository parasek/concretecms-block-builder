<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<hr class="field-entry-hr">

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowEndingField]"
           id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowEndingField]"
           value="1"
        <?= '<% if (parseInt(linkFromSitemapShowEndingField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowEndingField]"
           class="form-check-label"
    ><?= t('Show the "Custom string at the end of URL" field'); ?></label>
</div>

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTextField]"
           id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTextField]"
           value="1"
        <?= '<% if (parseInt(linkFromSitemapShowTextField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTextField]"
           class="form-check-label"
    ><?= t('Show the "Text" field'); ?> <span class="text-muted">- <?= t('The text inside the %s tag, e.g., %s', h('<a>'), h('<a href="#">Example text</a>')); ?></span></label>
</div>

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTitleField]"
           id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTitleField]"
           value="1"
    <?= '<% if (parseInt(linkFromSitemapShowTitleField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowTitleField]"
           class="form-check-label"
    ><?= t('Show the "Title" field'); ?> <span class="text-muted">- <?= t('The value of the title attribute, e.g., %s', h('<a href="#" title="Example title"></a>')); ?></span></label>
</div>

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNewWindowField]"
           id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNewWindowField]"
           value="1"
    <?= '<% if (parseInt(linkFromSitemapShowNewWindowField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNewWindowField]"
           class="form-check-label"
    ><?= t('Show the "Open in new window" field'); ?></label>
</div>

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNoFollowField]"
           id="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNoFollowField]"
           value="1"
    <?= '<% if (parseInt(linkFromSitemapShowNoFollowField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][linkFromSitemapShowNoFollowField]"
           class="form-check-label"
    ><?= t('Show the "Add nofollow attribute" field'); ?></label>
</div>
