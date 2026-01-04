<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-field-entry-hr">

    <div class="">
        <label for="<%=context%>[<%=counter%>][datePickerPattern]"
               class="form-label"
        ><?= t('PHP date pattern'); ?></label>
        <input type="text"
               id="<%=context%>[<%=counter%>][datePickerPattern]"
               name="<%=context%>[<%=counter%>][datePickerPattern]"
               class="form-control"
               value="<%=datePickerPattern%>"
        >
        <div class="form-text">
            <?= t('Check the %sPHP manual%s for available formats. Examples: <code>d.m.Y</code>, <code>d-m-Y</code>, <code>Y-m-d</code>, <code>m-d-Y</code>, <code>m/d/Y</code>', '<a href="https://www.php.net/manual/en/function.date.php" target="_blank" rel="noopener noreferrer">', '</a>'); ?>
        </div>
    </div>

</script>
