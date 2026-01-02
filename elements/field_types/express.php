<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<hr class="field-entry-hr">

<div class="<% if (error['expressHandle'] !== undefined) { %>has-error<% } %>">
    <label for="<%=groupHandle%>[<%=counter%>][expressHandle]"
           class="form-label"
    ><?= t('Express object handle'); ?> *</label>

    <input type="text"
           id="<%=groupHandle%>[<%=counter%>][expressHandle]"
           name="<%=groupHandle%>[<%=counter%>][expressHandle]"
           class="form-control"
           value="<%=expressHandle%>"
    >
</div>
