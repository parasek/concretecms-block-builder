<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<hr class="field-entry-hr">

<div class="form-check">
    <input type="checkbox"
           class="form-check-input"
           name="<%=groupHandle%>[<%=counter%>][imageShowAltTextField]"
           id="<%=groupHandle%>[<%=counter%>][imageShowAltTextField]"
           value="1"
        <?= '<% if (parseInt(imageShowAltTextField)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][imageShowAltTextField]"
           class="form-check-label"
    ><?= t('Show the "Alt text" field'); ?></label>
</div>

<div class="form-check">
    <input type="checkbox"
           name="<%=groupHandle%>[<%=counter%>][imageCreateThumbnailImage]"
           id="<%=groupHandle%>[<%=counter%>][imageCreateThumbnailImage]"
           value="1"
           class="form-check-input js-image-create-thumbnail-image"
        <?= '<% if (parseInt(imageCreateThumbnailImage)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][imageCreateThumbnailImage]"
           class="form-check-label"
    ><?= t('Generate a thumbnail using the image helper (if the original image is larger than the specified dimensions)'); ?></label>
</div>

<div class="row mt-2 js-image-create-thumbnail-image-wrapper <% if (error['imageThumbnailOptions'] !== undefined) { %>has-error<% } %>"
     id="<%=groupHandle%>[<%=counter%>][imageThumbnailOptions]"
    <?= '<% if (!parseInt(imageCreateThumbnailImage)) { %> style="display: none;" <% } %>'; ?>
>

    <div class="col-lg-4 mb-4 <% if (error['imageThumbnailWidth'] !== undefined) { %>has-error<% } %>">
        <label for="<%=groupHandle%>[<%=counter%>][imageThumbnailWidth]"
               class="form-label"
        ><?= t('Width'); ?></label>
        <div class="input-group">
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][imageThumbnailWidth]"
                   name="<%=groupHandle%>[<%=counter%>][imageThumbnailWidth]"
                   class="form-control"
                   value="<%=imageThumbnailWidth%>"
            >
            <span class="input-group-text">px</span>
        </div>
    </div>

    <div class="col-lg-4 mb-4 <% if (error['imageThumbnailHeight'] !== undefined) { %>has-error<% } %>">
        <label for="<%=groupHandle%>[<%=counter%>][imageThumbnailHeight]"
               class="form-label"
        ><?= t('Height'); ?></label>
        <div class="input-group">
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][imageThumbnailHeight]"
                   name="<%=groupHandle%>[<%=counter%>][imageThumbnailHeight]"
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
                   name="<%=groupHandle%>[<%=counter%>][imageThumbnailCrop]"
                   id="<%=groupHandle%>[<%=counter%>][imageThumbnailCrop]"
                   value="1"
                <?= '<% if (parseInt(imageThumbnailCrop)) { %> checked="checked" <% } %>'; ?>
            >
            <label for="<%=groupHandle%>[<%=counter%>][imageThumbnailCrop]"
                   class="form-check-label"
            ><?= t('Yes'); ?></label>
        </div>
    </div>

    <div class="col-lg-2 mb-4">
        <label class="form-label"><?= t('Editable'); ?></label>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][imageThumbnailEditable]"
                   id="<%=groupHandle%>[<%=counter%>][imageThumbnailEditable]"
                   value="1"
                <?= '<% if (parseInt(imageThumbnailEditable)) { %> checked="checked" <% } %>'; ?>
            >
            <label for="<%=groupHandle%>[<%=counter%>][imageThumbnailEditable]"
                   class="form-check-label"
            ><?= t('Yes'); ?></label>
        </div>
    </div>

</div>

<div class="form-check">
    <input type="checkbox"
           name="<%=groupHandle%>[<%=counter%>][imageCreateFullscreenImage]"
           id="<%=groupHandle%>[<%=counter%>][imageCreateFullscreenImage]"
           value="1"
           class="form-check-input js-image-create-fullscreen-image"
        <?= '<% if (parseInt(imageCreateFullscreenImage)) { %> checked="checked" <% } %>'; ?>
    >
    <label for="<%=groupHandle%>[<%=counter%>][imageCreateFullscreenImage]"
           class="form-check-label"
    ><?= t('Generate a fullscreen image using the image helper (if the original image is larger than the specified dimensions)'); ?></label>
</div>

<div class="row mt-2 js-image-create-fullscreen-image-wrapper <% if (error['imageFullscreenOptions'] !== undefined) { %>has-error<% } %>"
     id="<%=groupHandle%>[<%=counter%>][imageFullscreenOptions]"
    <?= '<% if (!parseInt(imageCreateFullscreenImage)) { %> style="display: none;" <% } %>'; ?>
>

    <div class="col-lg-4 mb-4 <% if (error['imageFullscreenWidth'] !== undefined) { %>has-error<% } %>">
        <label for="<%=groupHandle%>[<%=counter%>][imageFullscreenWidth]"
               class="form-label"
        ><?= t('Width'); ?></label>
        <div class="input-group">
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][imageFullscreenWidth]"
                   name="<%=groupHandle%>[<%=counter%>][imageFullscreenWidth]"
                   class="form-control"
                   value="<%=imageFullscreenWidth%>"
            >
            <span class="input-group-text">px</span>
        </div>
    </div>

    <div class="col-lg-4 mb-4 <% if (error['imageFullscreenHeight'] !== undefined) { %>has-error<% } %>">
        <label for="<%=groupHandle%>[<%=counter%>][imageFullscreenHeight]"
               class="form-label"
        ><?= t('Height'); ?></label>
        <div class="input-group">
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][imageFullscreenHeight]"
                   name="<%=groupHandle%>[<%=counter%>][imageFullscreenHeight]"
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
                   name="<%=groupHandle%>[<%=counter%>][imageFullscreenCrop]"
                   id="<%=groupHandle%>[<%=counter%>][imageFullscreenCrop]"
                   value="1"
                <?= '<% if (parseInt(imageFullscreenCrop)) { %> checked="checked" <% } %>'; ?>
            >
            <label for="<%=groupHandle%>[<%=counter%>][imageFullscreenCrop]"
                   class="form-check-label"
            ><?= t('Yes'); ?></label>
        </div>
    </div>

    <div class="col-lg-2 mb-4">
        <label class="form-label"><?= t('Editable'); ?></label>
        <div class="form-check">
            <input type="checkbox"
                   class="form-check-input"
                   name="<%=groupHandle%>[<%=counter%>][imageFullscreenEditable]"
                   id="<%=groupHandle%>[<%=counter%>][imageFullscreenEditable]"
                   value="1"
                <?= '<% if (parseInt(imageFullscreenEditable)) { %> checked="checked" <% } %>'; ?>
            >
            <label for="<%=groupHandle%>[<%=counter%>][imageFullscreenEditable]"
                   class="form-check-label"
            ><?= t('Yes'); ?></label>
        </div>
    </div>

</div>
