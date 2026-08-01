<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="mb-4">
        <label class="form-label" for="<%=context%>[<%=counter%>][defaultValue]"><?= t('Default value'); ?></label>
        <textarea class="form-control" id="<%=context%>[<%=counter%>][defaultValue]" name="<%=context%>[<%=counter%>][defaultValue]" rows="3"><%=defaultValue%></textarea>
    </div>

    <div class="mb-4">
        <label class="form-label" for="<%=context%>[<%=counter%>][placeholder]"><?= t('Placeholder'); ?></label>
        <input class="form-control" id="<%=context%>[<%=counter%>][placeholder]" maxlength="255" name="<%=context%>[<%=counter%>][placeholder]" type="text" value="<%=placeholder%>">
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <label class="form-label" for="<%=context%>[<%=counter%>][minimumLength]"><?= t('Minimum length'); ?></label>
            <div class="input-group">
                <input class="form-control" id="<%=context%>[<%=counter%>][minimumLength]" min="0" max="65535" name="<%=context%>[<%=counter%>][minimumLength]" step="1" type="number" value="<%=minimumLength%>">
                <span class="input-group-text"><?= t('chars'); ?></span>
            </div>
            <div class="form-text"><?= t('Leave empty for no minimum length.'); ?></div>
        </div>
        <div class="col-lg-6 mb-4">
            <label class="form-label" for="<%=context%>[<%=counter%>][maximumLength]"><?= t('Maximum length'); ?></label>
            <div class="input-group">
                <input class="form-control" id="<%=context%>[<%=counter%>][maximumLength]" min="1" max="65535" name="<%=context%>[<%=counter%>][maximumLength]" step="1" type="number" value="<%=maximumLength%>">
                <span class="input-group-text"><?= t('chars'); ?></span>
            </div>
            <div class="form-text"><?= t('Leave empty for no maximum length.'); ?></div>
        </div>
    </div>

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
