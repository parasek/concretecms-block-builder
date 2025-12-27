<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var array<string, string> $textAdditionalValidations
 */
?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="row">
        <div class="col-lg-6 mb-4">
            <label class="form-label" for="<%-context%>[<%-counter%>][defaultValue]"><?= t('Default value'); ?></label>
            <input class="form-control" id="<%-context%>[<%-counter%>][defaultValue]" maxlength="255" name="<%-context%>[<%-counter%>][defaultValue]" type="text" value="<%-defaultValue%>">
        </div>
        <div class="col-lg-6 mb-4">
            <label class="form-label" for="<%-context%>[<%-counter%>][placeholder]"><?= t('Placeholder'); ?></label>
            <input class="form-control" id="<%-context%>[<%-counter%>][placeholder]" maxlength="255" name="<%-context%>[<%-counter%>][placeholder]" type="text" value="<%-placeholder%>">
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label" for="<%-context%>[<%-counter%>][additionalValidation]"><?= t('Additional validation'); ?></label>
        <select class="form-select" id="<%-context%>[<%-counter%>][additionalValidation]" name="<%-context%>[<%-counter%>][additionalValidation]">
            <?php foreach ($textAdditionalValidations as $validationHandle => $validationLabel): ?>
                <?php $selected = "<% if (additionalValidation === '" . h($validationHandle) . "') { %>selected<% } %>"; ?>
                <option value="<?= h($validationHandle); ?>" <?= $selected; ?>><?= h($validationLabel); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <label class="form-label" for="<%-context%>[<%-counter%>][minimumLength]"><?= t('Minimum length'); ?></label>
            <div class="input-group">
                <input class="form-control" id="<%-context%>[<%-counter%>][minimumLength]" min="0" max="255" name="<%-context%>[<%-counter%>][minimumLength]" step="1" type="number" value="<%-minimumLength%>">
                <span class="input-group-text"><?= t('characters'); ?></span>
            </div>
            <div class="form-text"><?= t('Leave empty for no minimum length.'); ?></div>
        </div>
        <div class="col-lg-6 mb-4">
            <label class="form-label" for="<%-context%>[<%-counter%>][maximumLength]"><?= t('Maximum length'); ?></label>
            <div class="input-group">
                <input class="form-control" id="<%-context%>[<%-counter%>][maximumLength]" min="1" max="255" name="<%-context%>[<%-counter%>][maximumLength]" step="1" type="number" value="<%-maximumLength%>">
                <span class="input-group-text"><?= t('characters'); ?></span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <label class="form-label" for="<%-context%>[<%-counter%>][prefix]"><?= t('Field prefix'); ?></label>
            <input class="form-control" id="<%-context%>[<%-counter%>][prefix]" maxlength="100" name="<%-context%>[<%-counter%>][prefix]" type="text" value="<%-prefix%>">
            <div class="form-text"><?= t('Text displayed before the input, such as a currency symbol.'); ?></div>
        </div>
        <div class="col-lg-6 mb-4">
            <label class="form-label" for="<%-context%>[<%-counter%>][suffix]"><?= t('Field suffix'); ?></label>
            <input class="form-control" id="<%-context%>[<%-counter%>][suffix]" maxlength="100" name="<%-context%>[<%-counter%>][suffix]" type="text" value="<%-suffix%>">
            <div class="form-text"><?= t('Text displayed after the input, such as a percent sign.'); ?></div>
        </div>
    </div>

    <div class="form-check">
        <input
            class="form-check-input"
            id="<%-context%>[<%-counter%>][displayZeroValue]"
            name="<%-context%>[<%-counter%>][displayZeroValue]"
            type="checkbox"
            value="1"
        <% if (displayZeroValue === true || displayZeroValue === 1 || displayZeroValue === '1') { %> checked="checked" <% } %>
        >
        <label
            class="form-check-label"
            for="<%-context%>[<%-counter%>][displayZeroValue]"
        ><?= t('Display zero ("0") in the view template'); ?></label>
    </div>

</script>
