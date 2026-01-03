<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="field-entry-hr">

    <div class="row mb-4">
        <div class="col-xl-4">
            <label for="<%=context%>[<%=counter%>][wysiwygEditorHeight]"
                   class="form-label"
            ><?= t('Height'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%=context%>[<%=counter%>][wysiwygEditorHeight]"
                       name="<%=context%>[<%=counter%>][wysiwygEditorHeight]"
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

    <div class="">

        <label for="<%=context%>[<%=counter%>][wysiwygCustomConfig]"
               class="form-label"
        ><?= t('Custom editor configuration'); ?></label>
        <textarea id="<%=context%>[<%=counter%>][wysiwygCustomConfig]"
                  name="<%=context%>[<%=counter%>][wysiwygCustomConfig]"
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

</script>
