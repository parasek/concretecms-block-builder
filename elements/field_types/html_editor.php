<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<hr class="field-entry-hr">

<div class="row <% if (error['htmlEditorHeight'] !== undefined) { %>has-error<% } %>">
    <div class="col-xl-4">
        <label for="<%=groupHandle%>[<%=counter%>][htmlEditorHeight]" class="form-label"><?= t('Height'); ?></label>
        <div class="input-group">
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][htmlEditorHeight]"
                   name="<%=groupHandle%>[<%=counter%>][htmlEditorHeight]"
                   class="form-control"
                   value="<%=htmlEditorHeight%>"
            >
            <span class="input-group-text">px</span>
        </div>
        <div class="form-text"><?= t('Default height: %s', '250px'); ?></div>
    </div>
</div>
