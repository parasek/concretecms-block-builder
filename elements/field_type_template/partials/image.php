<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-field-entry-hr">

    <div class="form-check">
        <input type="checkbox"
               class="form-check-input"
               name="<%=context%>[<%=counter%>][imageShowAltTextField]"
               id="<%=context%>[<%=counter%>][imageShowAltTextField]"
               value="1"
            <?= '<% if (parseInt(imageShowAltTextField)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][imageShowAltTextField]"
               class="form-check-label"
        ><?= t('Show the "Alt text" field'); ?></label>
    </div>

    <div class="form-check">
        <input type="checkbox"
               name="<%=context%>[<%=counter%>][imageCreateThumbnailImage]"
               id="<%=context%>[<%=counter%>][imageCreateThumbnailImage]"
               value="1"
               class="form-check-input"
               data-image-create-thumbnail-image
            <?= '<% if (parseInt(imageCreateThumbnailImage)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][imageCreateThumbnailImage]"
               class="form-check-label"
        ><?= t('Generate a thumbnail using the image helper (if the original image is larger than the specified dimensions)'); ?></label>
    </div>

    <div class="row mt-2 <?= '<% if (!parseInt(imageCreateThumbnailImage)) { %>d-none<% } %>'; ?>"
         id="<%=context%>[<%=counter%>][imageThumbnailOptions]"
         data-image-create-thumbnail-image-wrapper
    >

        <div class="col-lg-4 mb-4">
            <label for="<%=context%>[<%=counter%>][imageThumbnailWidth]"
                   class="form-label"
            ><?= t('Width'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%=context%>[<%=counter%>][imageThumbnailWidth]"
                       name="<%=context%>[<%=counter%>][imageThumbnailWidth]"
                       class="form-control"
                       value="<%=imageThumbnailWidth%>"
                >
                <span class="input-group-text">px</span>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <label for="<%=context%>[<%=counter%>][imageThumbnailHeight]"
                   class="form-label"
            ><?= t('Height'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%=context%>[<%=counter%>][imageThumbnailHeight]"
                       name="<%=context%>[<%=counter%>][imageThumbnailHeight]"
                       class="form-control"
                       value="<%=imageThumbnailHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
        </div>

        <div class="col-lg-2 mb-4">
            <label class="form-label"><?= t('Crop'); ?></label>
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="<%=context%>[<%=counter%>][imageThumbnailCrop]"
                       id="<%=context%>[<%=counter%>][imageThumbnailCrop]"
                       value="1"
                    <?= '<% if (parseInt(imageThumbnailCrop)) { %> checked="checked" <% } %>'; ?>
                >
                <label for="<%=context%>[<%=counter%>][imageThumbnailCrop]"
                       class="form-check-label"
                ><?= t('Yes'); ?></label>
            </div>
        </div>

        <div class="col-lg-2 mb-4">
            <label class="form-label"><?= t('Editable'); ?></label>
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="<%=context%>[<%=counter%>][imageThumbnailEditable]"
                       id="<%=context%>[<%=counter%>][imageThumbnailEditable]"
                       value="1"
                    <?= '<% if (parseInt(imageThumbnailEditable)) { %> checked="checked" <% } %>'; ?>
                >
                <label for="<%=context%>[<%=counter%>][imageThumbnailEditable]"
                       class="form-check-label"
                ><?= t('Yes'); ?></label>
            </div>
        </div>

    </div>

    <div class="form-check">
        <input type="checkbox"
               name="<%=context%>[<%=counter%>][imageCreateFullscreenImage]"
               id="<%=context%>[<%=counter%>][imageCreateFullscreenImage]"
               value="1"
               class="form-check-input"
               data-image-create-fullscreen-image
            <?= '<% if (parseInt(imageCreateFullscreenImage)) { %> checked="checked" <% } %>'; ?>
        >
        <label for="<%=context%>[<%=counter%>][imageCreateFullscreenImage]"
               class="form-check-label"
        ><?= t('Generate a fullscreen image using the image helper (if the original image is larger than the specified dimensions)'); ?></label>
    </div>

    <div class="row mt-2 <?= '<% if (!parseInt(imageCreateFullscreenImage)) { %>d-none<% } %>'; ?>"
         id="<%=context%>[<%=counter%>][imageFullscreenOptions]"
         data-image-create-fullscreen-image-wrapper
    >

        <div class="col-lg-4 mb-4">
            <label for="<%=context%>[<%=counter%>][imageFullscreenWidth]"
                   class="form-label"
            ><?= t('Width'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%=context%>[<%=counter%>][imageFullscreenWidth]"
                       name="<%=context%>[<%=counter%>][imageFullscreenWidth]"
                       class="form-control"
                       value="<%=imageFullscreenWidth%>"
                >
                <span class="input-group-text">px</span>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <label for="<%=context%>[<%=counter%>][imageFullscreenHeight]"
                   class="form-label"
            ><?= t('Height'); ?></label>
            <div class="input-group">
                <input type="text"
                       id="<%=context%>[<%=counter%>][imageFullscreenHeight]"
                       name="<%=context%>[<%=counter%>][imageFullscreenHeight]"
                       class="form-control"
                       value="<%=imageFullscreenHeight%>"
                >
                <span class="input-group-text">px</span>
            </div>
        </div>

        <div class="col-lg-2 mb-4">
            <label class="form-label"><?= t('Crop'); ?></label>
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="<%=context%>[<%=counter%>][imageFullscreenCrop]"
                       id="<%=context%>[<%=counter%>][imageFullscreenCrop]"
                       value="1"
                    <?= '<% if (parseInt(imageFullscreenCrop)) { %> checked="checked" <% } %>'; ?>
                >
                <label for="<%=context%>[<%=counter%>][imageFullscreenCrop]"
                       class="form-check-label"
                ><?= t('Yes'); ?></label>
            </div>
        </div>

        <div class="col-lg-2 mb-4">
            <label class="form-label"><?= t('Editable'); ?></label>
            <div class="form-check">
                <input type="checkbox"
                       class="form-check-input"
                       name="<%=context%>[<%=counter%>][imageFullscreenEditable]"
                       id="<%=context%>[<%=counter%>][imageFullscreenEditable]"
                       value="1"
                    <?= '<% if (parseInt(imageFullscreenEditable)) { %> checked="checked" <% } %>'; ?>
                >
                <label for="<%=context%>[<%=counter%>][imageFullscreenEditable]"
                       class="form-check-label"
                ><?= t('Yes'); ?></label>
            </div>
        </div>

    </div>

</script>
