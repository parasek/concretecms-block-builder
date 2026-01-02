<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<hr class="field-entry-hr">

<div class="row <% if (error['textareaHeight'] !== undefined) { %>has-error<% } %>">
    <div class="col-xl-4">
        <label for="<%=groupHandle%>[<%=counter%>][textareaHeight]"
               class="form-label"
        ><?= t('Height'); ?></label>
        <div class="input-group">
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][textareaHeight]"
                   name="<%=groupHandle%>[<%=counter%>][textareaHeight]"
                   class="form-control"
                   value="<%=textareaHeight%>"
            >
            <span class="input-group-text">px</span>
        </div>
    </div>
    <div class="form-text"><?= t('Default height: %s', '66px'); ?></div>
</div>
