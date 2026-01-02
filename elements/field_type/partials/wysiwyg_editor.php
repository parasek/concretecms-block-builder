<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<hr class="field-entry-hr">

<div class="row mb-4 <% if (error['wysiwygEditorHeight'] !== undefined) { %>has-error<% } %>">
    <div class="col-xl-4">
        <label for="<%=groupHandle%>[<%=counter%>][wysiwygEditorHeight]"
               class="form-label"
        ><?= t('Height'); ?></label>
        <div class="input-group">
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][wysiwygEditorHeight]"
                   name="<%=groupHandle%>[<%=counter%>][wysiwygEditorHeight]"
                   class="form-control"
                   value="<%=wysiwygEditorHeight%>"
            >
            <span class="input-group-text">px</span>
        </div>
        <div class="form-text">
            <?= t('Default height of the editable area: %s.', '40px'); ?>
            <br>
            <?= t('Editor auto-grow will be enabled if you leave this field empty.'); ?>
        </div>
    </div>
</div>

<div class="<% if (error['wysiwygCustomConfig'] !== undefined) { %>has-error<% } %>">

    <label for="<%=groupHandle%>[<%=counter%>][wysiwygCustomConfig]"
           class="form-label"
    ><?= t('Custom editor configuration'); ?></label>
    <textarea id="<%=groupHandle%>[<%=counter%>][wysiwygCustomConfig]"
              name="<%=groupHandle%>[<%=counter%>][wysiwygCustomConfig]"
              class="form-control"
    ><%=wysiwygCustomConfig%></textarea>
    <div class="form-text">
        <?= t('The custom editor configuration should be inserted as JSON.'); ?>
        <br>
        <?= t('Example configuration:'); ?>
        <code class="bb-code-block">
            {
            <br>&nbsp;&nbsp;"toolbar": [
            <br>&nbsp;&nbsp;&nbsp;&nbsp;{
            <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"name": "document",
            <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"items": ["Source", "-"]
            <br>&nbsp;&nbsp;&nbsp;&nbsp;},
            <br>&nbsp;&nbsp;&nbsp;&nbsp;{
            <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"name": "basicstyles",
            <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"items": ["Bold", "Italic", "Underline", "Strike", "Subscript",
            "Superscript", "-", "RemoveFormat"]
            <br>&nbsp;&nbsp;&nbsp;&nbsp;},
            <br>&nbsp;&nbsp;&nbsp;&nbsp;{
            <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"name": "styles",
            <br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"items": ["Styles", "Format"]
            <br>&nbsp;&nbsp;&nbsp;&nbsp;}
            <br>&nbsp;&nbsp;]
            <br>}
        </code>
    </div>
</div>
