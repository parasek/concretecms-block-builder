<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="field-entry-hr">

    <div class="">
        <label for="<%=context%>[<%=counter%>][fileSetPrefix]"
               class="form-label"
        ><?= t('Restrict File Set selection to those starting with:'); ?></label>

        <input type="text"
               id="<%=context%>[<%=counter%>][fileSetPrefix]"
               name="<%=context%>[<%=counter%>][fileSetPrefix]"
               class="form-control"
               value="<%=fileSetPrefix%>"
        >
    </div>

</script>
