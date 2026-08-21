<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var BlockBuilder\FieldType\FieldTypeInterface[] $fieldTypes
 * @var array $selectFieldTypes
 * @var array $selectFieldListGenerationMethods
 * @var array $selectMultipleFieldTypes
 * @var array $textAdditionalValidations
 * @var array $filesFromFolderOrders
 */
?>

<script type="text/template" id="templateEntry">

    <div class="bb-entry mb-4"
         data-entry
         data-counter="<%-counter%>"
    >

        <div class="bb-entry-header position-relative">

            <div class="bb-entry-header-move-entry text-body text-body-hover"
                 data-move-entry
            ><i class="fas fa-arrows-alt"></i></div>

            <div class="bb-entry-header-toggle-entry text-body text-body-hover"
                 data-toggle-entry
                 data-action="collapse"
            ><i class="far fa-minus-square"></i></div>

            <div class="bb-entry-header-title text-body">
                <strong data-entry-title>
                    <% if (label) { %>
                    <%-label%>
                    <% } else { %>
                    #<%-counter%>
                    <% } %>
                </strong><span
                    data-entry-required-indicator
                    aria-hidden="true"
                    <% if (!(required === true || required === 1 || required === '1')) { %> hidden<% } %>
                > *</span><i class="<%-fieldTypeIcon%> m-2 ms-2"></i><span><%-fieldTypeName%></span>
            </div>

            <div class="bb-entry-header-remove-entry text-danger text-danger-hover"
                 data-remove-entry
                 data-confirm-text="<?= t('Are you sure?'); ?>"
            ><i class="fas fa-times"></i></div>

        </div>

        <div class="bb-entry-content"
             data-entry-content
        >

            <input type="hidden"
                   id="<%-context%>[<%-counter%>][fieldType]"
                   name="<%-context%>[<%-counter%>][fieldType]"
                   value="<%-fieldTypeHandle%>"
            >

            <div class="row">

                <div class="col-lg-6 mb-4">
                    <label for="<%-context%>[<%-counter%>][label]" class="form-label">
                        <?= t('Label'); ?> *
                    </label>
                    <input type="text"
                           id="<%-context%>[<%-counter%>][label]"
                           name="<%-context%>[<%-counter%>][label]"
                           class="form-control"
                           value="<%-label%>"
                           data-entry-title-source
                    >
                    <div class="form-text"><?= t('Human-readable name, e.g., Product name'); ?></div>
                </div>

                <div class="col-lg-6 mb-4">
                    <label for="<%-context%>[<%-counter%>][handle]" class="form-label">
                        <?= t('Handle'); ?> *
                    </label>
                    <div class="bb-handle-input">
                        <input type="text"
                               id="<%-context%>[<%-counter%>][handle]"
                               name="<%-context%>[<%-counter%>][handle]"
                               class="form-control"
                               value="<%-handle%>"
                               maxlength="50"
                               data-entry-handle
                        >
                        <div
                            class="bb-handle-autogeneration-overlay"
                            data-handle-autogeneration-overlay
                            aria-hidden="true"
                        >
                            <i class="fas fa-circle-notch fa-spin"></i>
                            <?= t('Generating handle from label'); ?>
                        </div>
                    </div>
                    <div class="form-text"><?= t('Letters and underscores only, e.g., productName'); ?></div>
                </div>

            </div>

            <div class="row">

                <div class="col-lg-6 mb-4">

                    <div class="form-check">
                        <input type="checkbox"
                               class="form-check-input"
                               name="<%-context%>[<%-counter%>][required]"
                               id="<%-context%>[<%-counter%>][required]"
                               value="1"
                               data-entry-required-source
                        <% if (required === true || required === 1 || required === '1') { %> checked="checked" <% } %>
                        >
                        <label for="<%-context%>[<%-counter%>][required]"
                               class="form-check-label"
                        ><?= t('Required'); ?></label>
                    </div>

                    <% if (context === 'entries' && ['text_field', 'textarea'].includes(fieldTypeHandle)) { %>
                    <div class="form-check">
                        <input type="checkbox"
                               name="<%-context%>[<%-counter%>][titleSource]"
                               id="<%-context%>[<%-counter%>][titleSource]"
                               class="form-check-input"
                               data-use-field-as-title-in-repeatable-entries
                               value="1"
                        <% if (titleSource === true || titleSource === 1 || titleSource === '1') { %> checked="checked" <% } %>
                        >
                        <label for="<%-context%>[<%-counter%>][titleSource]"
                               class="form-check-label"
                        ><?= t('Use this field as the title in repeatable entries'); ?></label>
                    </div>
                    <% } %>

                </div>

                <div class="col-lg-6 mb-4">
                    <label for="<%-context%>[<%-counter%>][helpText]"
                           class="form-label"
                    ><?= t('Help text'); ?></label>
                    <input type="text"
                           id="<%-context%>[<%-counter%>][helpText]"
                           name="<%-context%>[<%-counter%>][helpText]"
                           class="form-control"
                           value="<%-helpText%>"
                    >
                    <div class="form-text"><?= t('This is a preview of the help text.'); ?></div>
                </div>

            </div>

            <%=partialContent%>

        </div>

    </div>

</script>

<?php
foreach ($fieldTypes as $fieldType) {
    View::element('field_type_template/partials/' . $fieldType::getFieldType()->value, [
        'handle' => $fieldType::getFieldType()->value,
        'selectFieldTypes' => $selectFieldTypes,
        'selectFieldListGenerationMethods' => $selectFieldListGenerationMethods,
        'selectMultipleFieldTypes' => $selectMultipleFieldTypes,
        'textAdditionalValidations' => $textAdditionalValidations,
        'filesFromFolderOrders' => $filesFromFolderOrders,
    ], 'block_builder');
}
?>
