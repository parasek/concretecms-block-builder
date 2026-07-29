<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

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
