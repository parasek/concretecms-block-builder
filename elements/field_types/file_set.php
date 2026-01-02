<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<hr class="field-entry-hr">

<div class="<% if (error['fileSetPrefix'] !== undefined) { %>has-error<% } %>">
    <label for="<%=groupHandle%>[<%=counter%>][fileSetPrefix]"
           class="form-label"
    ><?= t('Restrict File Set selection to those starting with:'); ?></label>

    <input type="text"
           id="<%=groupHandle%>[<%=counter%>][fileSetPrefix]"
           name="<%=groupHandle%>[<%=counter%>][fileSetPrefix]"
           class="form-control"
           value="<%=fileSetPrefix%>"
    >
</div>
