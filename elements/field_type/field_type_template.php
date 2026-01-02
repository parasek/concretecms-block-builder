<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var BlockBuilder\FieldType\Enum\FieldTypeEnum[] $fieldTypes
 * @var array $selectFieldTypes
 * @var array $selectFieldListGenerationMethods
 * @var array $selectMultipleFieldTypes
 */
?>

<script type="text/template" class="js-template-entries">

    <div class="field-entry mb-4 js-field-entry <% if (error) { %>field-entry-has-error<% } %>"
         data-counter="<%-counter%>"
    >

        <div class="field-entry-header position-relative">

            <div class="field-entry-header-move-entry text-body text-body-hover js-move-entry"
            ><i class="fas fa-arrows-alt"></i></div>

            <div class="field-entry-header-toggle-entry text-body text-body-hover js-toggle-entry"
                 data-action="collapse"
            ><i class="far fa-minus-square"></i></div>

            <div class="field-entry-header-title text-body">
                <strong class="js-field-entry-title">
                    <% if (label) { %>
                    <%-label%>
                    <% } else { %>
                    #<%-counter%>
                    <% } %>
                </strong><i class="<%-fieldTypeIcon%> m-2 ms-2"></i><span><%-fieldTypeName%></span>
            </div>

            <div class="field-entry-header-remove-entry text-danger text-danger-hover js-remove-entry"
                 data-confirm-text="<?= t('Are you sure?'); ?>"
            ><i class="fas fa-times"></i></div>

        </div>

        <div class="field-entry-content js-field-entry-content">

            <input type="hidden"
                   id="<%-groupHandle%>[<%-counter%>][fieldType]"
                   name="<%-groupHandle%>[<%-counter%>][fieldType]"
                   value="<%-fieldType%>"
            >

            <div class="row">

                <div class="col-lg-6 mb-4 <% if (error['label'] !== undefined) { %>has-error<% } %>">
                    <label for="<%-groupHandle%>[<%-counter%>][label]" class="form-label">
                        <?= t('Label'); ?> *
                    </label>
                    <input type="text"
                           id="<%-groupHandle%>[<%-counter%>][label]"
                           name="<%-groupHandle%>[<%-counter%>][label]"
                           class="form-control js-field-entry-title-source"
                           value="<%-label%>"
                    >
                    <div class="form-text"><?= t('Human-readable name, e.g., Product name'); ?></div>
                </div>

                <div class="col-lg-6 mb-4 <% if (error['handle'] !== undefined) { %>has-error<% } %>">
                    <label for="<%-groupHandle%>[<%-counter%>][handle]" class="form-label">
                        <?= t('Handle'); ?> *
                    </label>
                    <input type="text"
                           id="<%-groupHandle%>[<%-counter%>][handle]"
                           name="<%-groupHandle%>[<%-counter%>][handle]"
                           class="form-control"
                           value="<%-handle%>"
                           maxlength="50"
                    >
                    <div class="form-text"><?= t('Letters and underscores only, e.g., productName'); ?></div>
                </div>

            </div>

            <div class="row">

                <div class="col-lg-6 mb-4">

                    <div class="form-check">
                        <input type="checkbox"
                               class="form-check-input"
                               name="<%=groupHandle%>[<%=counter%>][required]"
                               id="<%=groupHandle%>[<%=counter%>][required]"
                               value="1"
                        <% if (parseInt(required)) { %> checked="checked" <% } %>
                        >
                        <label for="<%=groupHandle%>[<%=counter%>][required]"
                               class="form-check-label"
                        ><?= t('Required'); ?></label>
                    </div>

                    <% if (groupHandle === 'entries' && ['text_field', 'textarea'].includes(fieldType)) { %>
                    <div class="form-check">
                        <input type="checkbox"
                               name="<%=groupHandle%>[<%=counter%>][titleSource]"
                               id="<%=groupHandle%>[<%=counter%>][titleSource]"
                               class="form-check-input js-use-field-as-title-in-repeatable-entries"
                               value="1"
                        <% if (parseInt(titleSource)) { %> checked="checked" <% } %>
                        >
                        <label for="<%=groupHandle%>[<%=counter%>][titleSource]"
                               class="form-check-label"
                        ><?= t('Use this field as title in repeatable entries'); ?></label>
                    </div>
                    <% } %>

                </div>

                <div class="col-lg-6 mb-4">
                    <label for="<%=groupHandle%>[<%=counter%>][helpText]"
                           class="form-label"
                    ><?= t('Help text'); ?></label>
                    <input type="text"
                           id="<%=groupHandle%>[<%=counter%>][helpText]"
                           name="<%=groupHandle%>[<%=counter%>][helpText]"
                           class="form-control"
                           value="<%=helpText%>"
                    >
                    <div class="form-text"><?= t('This is a preview of the help text.'); ?></div>
                </div>

            </div>

            <?php
            foreach ($fieldTypes as $fieldType) {
                $handle = $fieldType->getHandle();
                echo '<% if (fieldType === "' . $handle . '") { %>';
                View::element('field_type/partials/' . $handle, [
                    'selectFieldTypes' => $selectFieldTypes,
                    'selectFieldListGenerationMethods' => $selectFieldListGenerationMethods,
                    'selectMultipleFieldTypes' => $selectMultipleFieldTypes,
                ], 'block_builder');
                echo '<% } %>';
            }
            ?>

        </div>

    </div>

</script>
