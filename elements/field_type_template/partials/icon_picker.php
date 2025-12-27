<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="mb-4">
        <label class="form-label" for="<%-context%>[<%-counter%>][defaultValue]"><?= t('Default value'); ?></label>
        <input class="form-control" id="<%-context%>[<%-counter%>][defaultValue]" maxlength="255" name="<%-context%>[<%-counter%>][defaultValue]" type="text" value="<%-defaultValue%>" placeholder="fas fa-star">
        <div class="form-text"><?= t('Enter the icon CSS classes, for example "fas fa-star".'); ?></div>
    </div>

</script>
