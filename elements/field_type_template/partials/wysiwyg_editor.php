<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="mb-4">
        <label class="form-label" for="<%=context%>[<%=counter%>][defaultValue]"><?= t('Default value'); ?></label>
        <textarea class="form-control" id="<%=context%>[<%=counter%>][defaultValue]" name="<%=context%>[<%=counter%>][defaultValue]" rows="4"><%=defaultValue%></textarea>
        <div class="form-text"><?= t('You may enter HTML supported by the WYSIWYG editor.'); ?></div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-4">
            <label for="<%=context%>[<%=counter%>][minHeight]"
                   class="form-label"
            ><?= t('Minimum height'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%=context%>[<%=counter%>][minHeight]"
                       name="<%=context%>[<%=counter%>][minHeight]"
                       class="form-control"
                       value="<%=minHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
            <div class="form-text">
                <?= t('Leave empty to use the editor default minimum height.'); ?>
            </div>
        </div>
        <div class="col-xl-4">
            <label for="<%=context%>[<%=counter%>][maxHeight]"
                   class="form-label"
            ><?= t('Maximum height'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%=context%>[<%=counter%>][maxHeight]"
                       name="<%=context%>[<%=counter%>][maxHeight]"
                       class="form-control"
                       value="<%=maxHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
            <div class="form-text">
                <?= t('Leave empty to allow the editor to grow without a maximum height.'); ?>
            </div>
        </div>
    </div>

    <div class="">

        <label for="<%=context%>[<%=counter%>][customConfig]"
               class="form-label"
        ><?= t('Custom editor configuration'); ?></label>
        <textarea id="<%=context%>[<%=counter%>][customConfig]"
                  name="<%=context%>[<%=counter%>][customConfig]"
                  class="form-control"
        ><%=customConfig%></textarea>
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
