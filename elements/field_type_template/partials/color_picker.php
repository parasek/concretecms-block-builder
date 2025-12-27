<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="mb-4">
        <label class="form-label" for="<%-context%>[<%-counter%>][defaultValue]"><?= t('Default value'); ?></label>
        <input class="form-control" id="<%-context%>[<%-counter%>][defaultValue]" maxlength="255" name="<%-context%>[<%-counter%>][defaultValue]" type="text" value="<%-defaultValue%>" placeholder="#000000">
        <div class="form-text"><?= t('Enter a hexadecimal, RGB, or RGBA color.'); ?></div>
    </div>

</script>
