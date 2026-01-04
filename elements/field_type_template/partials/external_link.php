<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-field-entry-hr">

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][externalLinkShowEndingField]"
               id="<%=context%>[<%=counter%>][externalLinkShowEndingField]"
               value="1"
            <?= '<% if (parseInt(externalLinkShowEndingField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][externalLinkShowEndingField]"
               class="form-check-label"
        ><?= t('Show the "Custom string at the end of URL" field'); ?></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][externalLinkShowTextField]"
               id="<%=context%>[<%=counter%>][externalLinkShowTextField]"
               value="1"
            <?= '<% if (parseInt(externalLinkShowTextField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][externalLinkShowTextField]"
               class="form-check-label"
        ><?= t('Show the "Text" field'); ?>
            <span class="text-muted">- <?= t('The text inside the %s tag, e.g., %s', h('<a>'), h('<a href="#">Example text</a>')); ?></span></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][externalLinkShowTitleField]"
               id="<%=context%>[<%=counter%>][externalLinkShowTitleField]"
               value="1"
            <?= '<% if (parseInt(externalLinkShowTitleField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][externalLinkShowTitleField]"
               class="form-check-label"
        ><?= t('Show the "Title" field'); ?>
            <span class="text-muted">- <?= t('The value of the title attribute, e.g., %s', h('<a href="#" title="Example title"></a>')); ?></span></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][externalLinkShowNewWindowField]"
               id="<%=context%>[<%=counter%>][externalLinkShowNewWindowField]"
               value="1"
            <?= '<% if (parseInt(externalLinkShowNewWindowField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][externalLinkShowNewWindowField]"
               class="form-check-label"
        ><?= t('Show the "Open in new window" field'); ?></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][externalLinkShowNoFollowField]"
               id="<%=context%>[<%=counter%>][externalLinkShowNoFollowField]"
               value="1"
            <?= '<% if (parseInt(externalLinkShowNoFollowField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][externalLinkShowNoFollowField]"
               class="form-check-label"
        ><?= t('Show the "Add nofollow attribute" field'); ?></label>
    </div>

</script>
