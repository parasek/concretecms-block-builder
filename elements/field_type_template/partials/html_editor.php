<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="mb-4">
        <label class="form-label" for="<%=context%>[<%=counter%>][defaultValue]"><?= t('Default value'); ?></label>
        <textarea class="form-control font-monospace" id="<%=context%>[<%=counter%>][defaultValue]" name="<%=context%>[<%=counter%>][defaultValue]" rows="4"><%=defaultValue%></textarea>
    </div>

    <div class="row">
        <div class="col-xl-4">
            <label for="<%=context%>[<%=counter%>][height]"
                   class="form-label"
            ><?= t('Height'); ?></label>
            <div class="input-group">
                <input type="number"
                       id="<%=context%>[<%=counter%>][height]"
                       name="<%=context%>[<%=counter%>][height]"
                       class="form-control"
                       min="40"
                       max="2000"
                       step="1"
                       value="<%=height%>"
                >
                <span class="input-group-text">px</span>
            </div>
            <div class="form-text"><?= t('Default height: %s', '250px'); ?></div>
        </div>
    </div>

</script>
