<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="row">
        <div class="col-xl-4">
            <label for="<%=context%>[<%=counter%>][htmlEditorHeight]" class="form-label"><?= t('Height'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%=context%>[<%=counter%>][htmlEditorHeight]"
                       name="<%=context%>[<%=counter%>][htmlEditorHeight]"
                       class="form-control"
                       value="<%=htmlEditorHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
            <div class="form-text"><?= t('Default height: %s', '250px'); ?></div>
        </div>
    </div>

</script>
