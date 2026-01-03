<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var BlockBuilder\FieldType\Enum\FieldTypeEnum[] $fieldTypes
 * @var BlockBuilder\FieldType\FieldTypeDtoInterface[] $fields
 * @var BlockBuilder\FieldType\Enum\FieldTypeContextEnum $fieldTypeContextEnum
 */
?>


<div class="field-type-actions d-flex flex-column flex-xxl-row gap-xxl-4"
     data-field-type-actions
>

    <label class="field-type-action-add label mb-3 mb-xxl-0">
        <select class=""
                data-add-entry
                data-context="<?= h($fieldTypeContextEnum->value); ?>"
                name="addEntry"
        >
            <option value="" data-icon="fas fa-plus"><?= t('Add a new field'); ?></option>
            <?php foreach ($fieldTypes as $fieldType): ?>
                <option value="<?= h($fieldType->getHandle()); ?>"
                        data-icon="<?= h($fieldType->getIcon()); ?>"
                        data-properties="<?= h(json_encode($fieldType->getProperties())); ?>"
                        data-default-values="<?= h(json_encode($fieldType->getDefaultValues())); ?>"
                ><?= h($fieldType->getLabel()); ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <div class="d-flex gap-4 flex-grow-xxl-1 align-items-xxl-center flex-xxl-grow-1">

        <div class="d-inline-block">
            <input type="checkbox"
                   name="scroll"
                   class="form-check-input"
                   value="1"
                   id="scroll-down-<?= h($fieldTypeContextEnum->value); ?>"
                   data-toggle-scroll
            >
            <label for="scroll-down-<?= h($fieldTypeContextEnum->value); ?>"
                   class="form-check-label"
            ><?= t('Scroll down'); ?></label>
        </div>

        <a href="#"
           class="text-body text-body-hover"
           data-expand-all
           title="<?= t('Expand all'); ?>"
        ><i class="far fa-plus-square me-2"></i><span class="d-none d-xl-inline"><?= t('Expand all'); ?></span></a>

        <a href="#"
           class="text-body text-body-hover"
           data-collapse-all
           title="<?= t('Collapse all'); ?>"
        ><i class="far fa-minus-square me-2"></i><span class="d-none d-xl-inline"><?= t('Collapse all'); ?></span></a>

        <a href="#"
           class="text-body text-body-hover"
           data-back-to-top
           title="<?= t('Back to top'); ?>"
        ><i class="far fa-caret-square-up me-2"></i><span class="d-none d-xl-inline"><?= t('Back to top'); ?></span></a>

        <a href="#"
           class="text-danger text-danger-hover ms-auto js-remove-all"
           title="<?= t('Remove all'); ?>"
           data-remove-all
           data-group-handle="<?= h($fieldTypeContextEnum->value); ?>"
           data-confirm-text="<?= t('Are you sure?'); ?>"
        ><i class="fas fa-times-circle me-2"></i><span class="d-none d-xl-inline"><?= t('Remove all'); ?></span></a>

    </div>

</div>

<div class="mt-4">
    <div id="bb-field-entries-<?= h($fieldTypeContextEnum->value); ?>"
         data-entries="<?= h(json_encode($fields)); ?>"
    ></div>
</div>
