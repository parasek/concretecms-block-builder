<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<hr class="field-entry-hr">

<div class="<% if (error['datePickerPattern'] !== undefined) { %>has-error<% } %>">
    <label for="<%=groupHandle%>[<%=counter%>][datePickerPattern]"
           class="form-label"
    ><?= t('PHP date pattern'); ?></label>
    <input type="text"
           id="<%=groupHandle%>[<%=counter%>][datePickerPattern]"
           name="<%=groupHandle%>[<%=counter%>][datePickerPattern]"
           class="form-control"
           value="<%=datePickerPattern%>"
    >
    <div class="form-text">
        <?= t('Check the %sPHP manual%s for available formats. Examples: <code>d.m.Y</code>, <code>d-m-Y</code>, <code>Y-m-d</code>, <code>m-d-Y</code>, <code>m/d/Y</code>', '<a href="https://www.php.net/manual/en/function.date.php" target="_blank" rel="noopener noreferrer">', '</a>'); ?>
    </div>
</div>
