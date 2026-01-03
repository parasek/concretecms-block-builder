<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="field-entry-hr">

    <div class="">
        <label for="<%=context%>[<%=counter%>][expressHandle]"
               class="form-label"
        ><?= t('Express object handle'); ?> *</label>

        <input type="text"
               id="<%=context%>[<%=counter%>][expressHandle]"
               name="<%=context%>[<%=counter%>][expressHandle]"
               class="form-control"
               value="<%=expressHandle%>"
        >
    </div>

</script>
