<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="row mb-4">
        <div class="col-xl-4">
            <label for="<%=context%>[<%=counter%>][minHeight]"
                   class="form-label"
            ><?= t('Minimum height'); ?></label>
            <div class="input-group">
                <input type="number"
                       id="<%=context%>[<%=counter%>][minHeight]"
                       name="<%=context%>[<%=counter%>][minHeight]"
                       class="form-control"
                       min="66"
                       max="2000"
                       step="1"
                       value="<%=minHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
            <div class="form-text"><?= t('Leave empty to use the default minimum height.'); ?></div>
        </div>
        <div class="col-xl-4">
            <label for="<%=context%>[<%=counter%>][maxHeight]"
                   class="form-label"
            ><?= t('Maximum height'); ?></label>
            <div class="input-group">
                <input type="number"
                       id="<%=context%>[<%=counter%>][maxHeight]"
                       name="<%=context%>[<%=counter%>][maxHeight]"
                       class="form-control"
                       min="66"
                       max="2000"
                       step="1"
                       value="<%=maxHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
            <div class="form-text"><?= t('Leave empty for unlimited height.'); ?></div>
        </div>
    </div>

    <div class="form-check">
        <input
            class="form-check-input"
            id="<%=context%>[<%=counter%>][displayZeroValue]"
            name="<%=context%>[<%=counter%>][displayZeroValue]"
            type="checkbox"
            value="1"
        <% if (displayZeroValue === true || displayZeroValue === 1 || displayZeroValue === '1') { %> checked="checked" <% } %>
        >
        <label
            class="form-check-label"
            for="<%=context%>[<%=counter%>][displayZeroValue]"
        ><?= t('Display zero ("0") in the view template'); ?></label>
    </div>

</script>
