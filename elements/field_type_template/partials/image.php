<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%-context%>[<%-counter%>][showAltTextField]"
               id="<%-context%>[<%-counter%>][showAltTextField]"
               value="1"
            <?= '<% if ((showAltTextField === true || showAltTextField === 1 || showAltTextField === \'1\')) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%-context%>[<%-counter%>][showAltTextField]"
               class="form-check-label"
        ><?= t('Show the "Alt text" field'); ?></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               name="<%-context%>[<%-counter%>][createThumbnailImage]"
               id="<%-context%>[<%-counter%>][createThumbnailImage]"
               value="1"
               class="form-check-input"
               data-image-create-thumbnail-image
            <?= '<% if ((createThumbnailImage === true || createThumbnailImage === 1 || createThumbnailImage === \'1\')) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%-context%>[<%-counter%>][createThumbnailImage]"
               class="form-check-label"
        ><?= t('Generate a thumbnail using the image helper class (if the original image is larger than the specified dimensions)'); ?></label>
    </div>

    <div class="row mt-2 <?= '<% if (!(createThumbnailImage === true || createThumbnailImage === 1 || createThumbnailImage === \'1\')) { %>d-none<% } %>'; ?>"
         id="<%-context%>[<%-counter%>][thumbnailOptions]"
         data-image-create-thumbnail-image-wrapper
    >

        <div class="col-lg-4 mb-4">
            <label for="<%-context%>[<%-counter%>][thumbnailWidth]"
                   class="form-label"
            ><?= t('Width'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%-context%>[<%-counter%>][thumbnailWidth]"
                       name="<%-context%>[<%-counter%>][thumbnailWidth]"
                       class="form-control"
                       value="<%-thumbnailWidth%>"
                >
                <span class="input-group-text">px</span>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <label for="<%-context%>[<%-counter%>][thumbnailHeight]"
                   class="form-label"
            ><?= t('Height'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%-context%>[<%-counter%>][thumbnailHeight]"
                       name="<%-context%>[<%-counter%>][thumbnailHeight]"
                       class="form-control"
                       value="<%-thumbnailHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
        </div>

        <div class="col-lg-2 mb-4">
            <label class="form-label"><?= t('Crop'); ?></label>
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="<%-context%>[<%-counter%>][thumbnailCrop]"
                       id="<%-context%>[<%-counter%>][thumbnailCrop]"
                       value="1"
                    <?= '<% if ((thumbnailCrop === true || thumbnailCrop === 1 || thumbnailCrop === \'1\')) { %> checked="checked" <% } %>'; ?>
                >
                <label for="<%-context%>[<%-counter%>][thumbnailCrop]"
                       class="form-check-label"
                ><?= t('Yes'); ?></label>
            </div>
        </div>

        <div class="col-lg-2 mb-4">
            <label class="form-label"><?= t('Editable dimensions'); ?></label>
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="<%-context%>[<%-counter%>][thumbnailEditable]"
                       id="<%-context%>[<%-counter%>][thumbnailEditable]"
                       value="1"
                    <?= '<% if ((thumbnailEditable === true || thumbnailEditable === 1 || thumbnailEditable === \'1\')) { %> checked="checked" <% } %>'; ?>
                >
                <label for="<%-context%>[<%-counter%>][thumbnailEditable]"
                       class="form-check-label"
                ><?= t('Yes'); ?></label>
            </div>
        </div>

    </div>

    <div class="form-check">
        <input type="checkbox"
               name="<%-context%>[<%-counter%>][createFullscreenImage]"
               id="<%-context%>[<%-counter%>][createFullscreenImage]"
               value="1"
               class="form-check-input"
               data-image-create-fullscreen-image
            <?= '<% if ((createFullscreenImage === true || createFullscreenImage === 1 || createFullscreenImage === \'1\')) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%-context%>[<%-counter%>][createFullscreenImage]"
               class="form-check-label"
        ><?= t('Generate a fullscreen image using the image helper class (if the original image is larger than the specified dimensions)'); ?></label>
    </div>

    <div class="row mt-2 <?= '<% if (!(createFullscreenImage === true || createFullscreenImage === 1 || createFullscreenImage === \'1\')) { %>d-none<% } %>'; ?>"
         id="<%-context%>[<%-counter%>][fullscreenOptions]"
         data-image-create-fullscreen-image-wrapper
    >

        <div class="col-lg-4 mb-4">
            <label for="<%-context%>[<%-counter%>][fullscreenWidth]"
                   class="form-label"
            ><?= t('Width'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%-context%>[<%-counter%>][fullscreenWidth]"
                       name="<%-context%>[<%-counter%>][fullscreenWidth]"
                       class="form-control"
                       value="<%-fullscreenWidth%>"
                >
                <span class="input-group-text">px</span>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <label for="<%-context%>[<%-counter%>][fullscreenHeight]"
                   class="form-label"
            ><?= t('Height'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%-context%>[<%-counter%>][fullscreenHeight]"
                       name="<%-context%>[<%-counter%>][fullscreenHeight]"
                       class="form-control"
                       value="<%-fullscreenHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
        </div>

        <div class="col-lg-2 mb-4">
            <label class="form-label"><?= t('Crop'); ?></label>
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="<%-context%>[<%-counter%>][fullscreenCrop]"
                       id="<%-context%>[<%-counter%>][fullscreenCrop]"
                       value="1"
                    <?= '<% if ((fullscreenCrop === true || fullscreenCrop === 1 || fullscreenCrop === \'1\')) { %> checked="checked" <% } %>'; ?>
                >
                <label for="<%-context%>[<%-counter%>][fullscreenCrop]"
                       class="form-check-label"
                ><?= t('Yes'); ?></label>
            </div>
        </div>

        <div class="col-lg-2 mb-4">
            <label class="form-label"><?= t('Editable dimensions'); ?></label>
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="<%-context%>[<%-counter%>][fullscreenEditable]"
                       id="<%-context%>[<%-counter%>][fullscreenEditable]"
                       value="1"
                    <?= '<% if ((fullscreenEditable === true || fullscreenEditable === 1 || fullscreenEditable === \'1\')) { %> checked="checked" <% } %>'; ?>
                >
                <label for="<%-context%>[<%-counter%>][fullscreenEditable]"
                       class="form-check-label"
                ><?= t('Yes'); ?></label>
            </div>
        </div>

    </div>

</script>
