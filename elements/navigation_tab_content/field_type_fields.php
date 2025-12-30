<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var BlockBuilder\FieldType\Enum\FieldTypeEnum[] $fieldTypes
 * @var BlockBuilder\FieldType\FieldTypeDtoInterface[] $fields
 * @var BlockBuilder\FieldType\Enum\FieldTypeContextEnum $fieldTypeContextEnum
 */
?>

<?php for ($i = 1; $i <= 2; $i++): ?>

    <div class="row">
        <div class="col-xl-3 mb-4">
            <label class="label w-100">
                <select class="js-add-entry form-select" data-group-handle="<?= h($fieldTypeContextEnum->value); ?>" name="addNewField">
                    <option value="" data-icon=""><?= t('+ Add new field'); ?></option>
                    <?php foreach ($fieldTypes as $fieldType): ?>
                        <option value="<?= h($fieldType->getHandle()); ?>"
                                data-icon="<?= h($fieldType->getIcon()); ?>"
                        ><?= h($fieldType->getLabel()); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="col-xl-9 mb-4 entries-actions d-flex flex-column flex-md-row align-items-md-center">
            <div class="entries-action entries-action-scroll form-check-inline">
                <input type="checkbox"
                       name="scroll"
                       class="js-toggle-scroll form-check-input"
                       value="1"
                       id="scroll-down-<?= h($fieldTypeContextEnum->value); ?>-<?= h($i); ?>"
                       <?php if (empty(app('cookie')->get('scrollDisabled'))): ?>checked="checked"<?php endif; ?>
                >
                <label for="scroll-down-<?= h($fieldTypeContextEnum->value); ?>-<?= h($i); ?>"
                       class="form-check-label"
                ><?= t('Scroll down'); ?></label>
            </div>
            <a href="#"
               class="entries-action js-expand-all"
            ><i class="far fa-plus-square"></i> <?= t('Expand all'); ?></a>
            <a href="#"
               class="entries-action js-collapse-all"
            ><i class="far fa-minus-square"></i> <?= t('Collapse all'); ?></a>
            <a href="#"
               class="entries-action entries-action-remove-all js-remove-all"
               data-group-handle="<?= h($fieldTypeContextEnum->value); ?>"
               data-confirm-text="<?= t('Are you sure?'); ?>"
            ><i class="fas fa-times-circle"></i> <?= t('Remove all'); ?></a>
        </div>
    </div>

    <?php if ($i === 1): ?>
        <div class="mb-4">
            <div id="field-types-<?= h($fieldTypeContextEnum->value); ?>"
                 class="js-sortable"
                 data-entries="<?= h(json_encode($fields)); ?>"
            ></div>
        </div>
    <?php endif; ?>

<?php endfor; ?>


