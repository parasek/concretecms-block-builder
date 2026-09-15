<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="mb-4">
        <label class="form-label" for="<%-context%>[<%-counter%>][defaultValue]"><?= t('Default value'); ?></label>
        <textarea class="form-control" id="<%-context%>[<%-counter%>][defaultValue]" name="<%-context%>[<%-counter%>][defaultValue]" rows="4"><%-defaultValue%></textarea>
        <div class="form-text"><?= t('You may enter any HTML supported by the WYSIWYG editor.'); ?></div>
    </div>

    <div class="row mb-4">
        <div class="col-xl-4">
            <label for="<%-context%>[<%-counter%>][minHeight]"
                   class="form-label"
            ><?= t('Minimum height'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%-context%>[<%-counter%>][minHeight]"
                       name="<%-context%>[<%-counter%>][minHeight]"
                       class="form-control"
                       value="<%-minHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
            <div class="form-text">
                <?= t('Leave empty to use the editor\'s default minimum height.'); ?>
            </div>
        </div>
        <div class="col-xl-4">
            <label for="<%-context%>[<%-counter%>][maxHeight]"
                   class="form-label"
            ><?= t('Maximum height'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%-context%>[<%-counter%>][maxHeight]"
                       name="<%-context%>[<%-counter%>][maxHeight]"
                       class="form-control"
                       value="<%-maxHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
            <div class="form-text">
                <?= t('Leave empty to allow the editor to grow without a maximum height.'); ?>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label" for="<%-context%>[<%-counter%>][loadPreset]"><?= t('Load preset'); ?></label>
        <select class="form-select"
                id="<%-context%>[<%-counter%>][loadPreset]"
                data-load-editor-preset
                data-confirm-text="<?= t('Loading this preset will overwrite Allowed Tags and Custom editor configuration. Continue?'); ?>">
            <option value="">---</option>
            <?php foreach (\BlockBuilder\FieldType\Type\WysiwygEditor\WysiwygEditorPresets::getAll() as $presetHandle => $preset) { ?>
                <option value="<?= h($presetHandle); ?>"
                        data-allowed-tags="<?= h($preset['allowedTags']); ?>"
                        data-custom-config="<?= h($preset['customConfig']); ?>"><?= $preset['label']; ?></option>
            <?php } ?>
        </select>
    </div>

    <div class="mb-4">
        <label class="form-label" for="<%-context%>[<%-counter%>][allowedTags]"><?= t('Allowed Tags'); ?></label>
        <input type="text" class="form-control"
               id="<%-context%>[<%-counter%>][allowedTags]"
               name="<%-context%>[<%-counter%>][allowedTags]"
               value="<%-allowedTags%>">
        <div class="form-text">
            <?= t('Enter tags to keep. Leave empty to allow all tags.'); ?>
            <code>&lt;span&gt;&lt;strong&gt;&lt;br&gt;</code>
        </div>
    </div>

    <div>

        <label for="<%-context%>[<%-counter%>][customConfig]"
               class="form-label"
        ><?= t('Custom editor configuration'); ?></label>
        <textarea id="<%-context%>[<%-counter%>][customConfig]"
                  name="<%-context%>[<%-counter%>][customConfig]"
                  class="form-control"
        ><%-customConfig%></textarea>
        <div class="form-text">
            <?= t('Enter the custom editor configuration as JSON.'); ?>
        </div>
    </div>

</script>
