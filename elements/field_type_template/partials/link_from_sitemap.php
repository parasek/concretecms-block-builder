<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-field-entry-hr">

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][linkFromSitemapShowEndingField]"
               id="<%=context%>[<%=counter%>][linkFromSitemapShowEndingField]"
               value="1"
            <?= '<% if (parseInt(linkFromSitemapShowEndingField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][linkFromSitemapShowEndingField]"
               class="form-check-label"
        ><?= t('Show the "Custom string at the end of URL" field'); ?></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][linkFromSitemapShowTextField]"
               id="<%=context%>[<%=counter%>][linkFromSitemapShowTextField]"
               value="1"
            <?= '<% if (parseInt(linkFromSitemapShowTextField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][linkFromSitemapShowTextField]"
               class="form-check-label"
        ><?= t('Show the "Text" field'); ?>
            <span class="text-muted">- <?= t('The text inside the %s tag, e.g., %s', h('<a>'), h('<a href="#">Example text</a>')); ?></span></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][linkFromSitemapShowTitleField]"
               id="<%=context%>[<%=counter%>][linkFromSitemapShowTitleField]"
               value="1"
            <?= '<% if (parseInt(linkFromSitemapShowTitleField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][linkFromSitemapShowTitleField]"
               class="form-check-label"
        ><?= t('Show the "Title" field'); ?>
            <span class="text-muted">- <?= t('The value of the title attribute, e.g., %s', h('<a href="#" title="Example title"></a>')); ?></span></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][linkFromSitemapShowNewWindowField]"
               id="<%=context%>[<%=counter%>][linkFromSitemapShowNewWindowField]"
               value="1"
            <?= '<% if (parseInt(linkFromSitemapShowNewWindowField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][linkFromSitemapShowNewWindowField]"
               class="form-check-label"
        ><?= t('Show the "Open in new window" field'); ?></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][linkFromSitemapShowNoFollowField]"
               id="<%=context%>[<%=counter%>][linkFromSitemapShowNoFollowField]"
               value="1"
            <?= '<% if (parseInt(linkFromSitemapShowNoFollowField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][linkFromSitemapShowNoFollowField]"
               class="form-check-label"
        ><?= t('Show the "Add nofollow attribute" field'); ?></label>
    </div>

</script>
