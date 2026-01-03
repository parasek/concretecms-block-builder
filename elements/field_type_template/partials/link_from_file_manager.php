<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="field-entry-hr">

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][linkFromFileManagerShowEndingField]"
               id="<%=context%>[<%=counter%>][linkFromFileManagerShowEndingField]"
               value="1"
            <?= '<% if (parseInt(linkFromFileManagerShowEndingField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][linkFromFileManagerShowEndingField]"
               class="form-check-label"
        ><?= t('Show the "Custom string at the end of URL" field'); ?></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][linkFromFileManagerShowTextField]"
               id="<%=context%>[<%=counter%>][linkFromFileManagerShowTextField]"
               value="1"
            <?= '<% if (parseInt(linkFromFileManagerShowTextField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][linkFromFileManagerShowTextField]"
               class="form-check-label"
        ><?= t('Show the "Text" field'); ?>
            <span class="text-muted">- <?= t('The text inside the %s tag, e.g., %s', h('<a>'), h('<a href="#">Example text</a>')); ?></span></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][linkFromFileManagerShowTitleField]"
               id="<%=context%>[<%=counter%>][linkFromFileManagerShowTitleField]"
               value="1"
            <?= '<% if (parseInt(linkFromFileManagerShowTitleField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][linkFromFileManagerShowTitleField]"
               class="form-check-label"
        ><?= t('Show the "Title" field'); ?>
            <span class="text-muted">- <?= t('The value of the title attribute, e.g., %s', h('<a href="#" title="Example title"></a>')); ?></span></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][linkFromFileManagerShowNewWindowField]"
               id="<%=context%>[<%=counter%>][linkFromFileManagerShowNewWindowField]"
               value="1"
            <?= '<% if (parseInt(linkFromFileManagerShowNewWindowField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][linkFromFileManagerShowNewWindowField]"
               class="form-check-label"
        ><?= t('Show the "Open in new window" field'); ?></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][linkFromFileManagerShowNoFollowField]"
               id="<%=context%>[<%=counter%>][linkFromFileManagerShowNoFollowField]"
               value="1"
            <?= '<% if (parseInt(linkFromFileManagerShowNoFollowField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][linkFromFileManagerShowNoFollowField]"
               class="form-check-label"
        ><?= t('Show the "Add nofollow attribute" field'); ?></label>
    </div>

</script>
