<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var BlockBuilder\FieldType\Enum\FieldTypeEnum[] $fieldTypes
 * @var BlockBuilder\FieldType\FieldTypeDtoInterface[] $fields
 * @var BlockBuilder\FieldType\Enum\FieldTypeContextEnum $fieldTypeContextEnum
 */
?>


<div class="field-type-actions d-flex flex-column flex-xxl-row gap-xxl-4">

    <label class="field-type-action-add label mb-3 mb-xxl-0">
        <select class="js-add-entry"
                data-group-handle="<?= h($fieldTypeContextEnum->value); ?>"
                name="addNewField"
        >
            <option value="" data-icon="fas fa-plus"><?= t('Add a new field'); ?></option>
            <?php foreach ($fieldTypes as $fieldType): ?>
                <option value="<?= h($fieldType->getHandle()); ?>"
                        data-icon="<?= h($fieldType->getIcon()); ?>"
                ><?= h($fieldType->getLabel()); ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <div class="d-flex gap-4 flex-grow-xxl-1 align-items-xxl-center flex-xxl-grow-1">

        <div class="d-inline-block">
            <input type="checkbox"
                   name="scroll"
                   class="js-toggle-scroll form-check-input"
                   value="1"
                   id="scroll-down-<?= h($fieldTypeContextEnum->value); ?>"
            >
            <label for="scroll-down-<?= h($fieldTypeContextEnum->value); ?>"
                   class="form-check-label"
            ><?= t('Scroll down'); ?></label>
        </div>

        <a href="#"
           class="text-body text-body-hover js-expand-all"
           title="<?= t('Expand all'); ?>"
        ><i class="far fa-plus-square me-2"></i><span class="d-none d-xl-inline"><?= t('Expand all'); ?></span></a>

        <a href="#"
           class="text-body text-body-hover js-collapse-all"
           title="<?= t('Collapse all'); ?>"
        ><i class="far fa-minus-square me-2"></i><span class="d-none d-xl-inline"><?= t('Collapse all'); ?></span></a>

        <a href="#"
           class="text-body text-body-hover js-back-to-top"
           title="<?= t('Back to top'); ?>"
        ><i class="far fa-caret-square-up me-2"></i><span class="d-none d-xl-inline"><?= t('Back to top'); ?></span></a>

        <a href="#"
           class="text-danger text-danger-hover ms-auto js-remove-all"
           title="<?= t('Remove all'); ?>"
           data-group-handle="<?= h($fieldTypeContextEnum->value); ?>"
           data-confirm-text="<?= t('Are you sure?'); ?>"
        ><i class="fas fa-times-circle me-2"></i><span class="d-none d-xl-inline"><?= t('Remove all'); ?></span></a>

    </div>

</div>

<div class="mt-4">
    <div id="field-types-<?= h($fieldTypeContextEnum->value); ?>"
         class="js-sortable"
         data-entries="<?= h(json_encode($fields)); ?>"
    ></div>
</div>
