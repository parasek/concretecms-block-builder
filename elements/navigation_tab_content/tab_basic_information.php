<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var Concrete\Core\Form\Service\Form $form
 * @var BlockBuilder\Block\Dto\CreateBlockDto $config
 * @var array $fieldsWithError
 * @var array $fieldTypes
 * @var BlockBuilder\FieldType\FieldTypeDtoInterface[] $basic
 */
?>

<div class="row">
    <div class="col-lg-3 mb-4">
        <select class="js-add-entry form-select" data-group-handle="basic">
            <?php foreach ($fieldTypes as $k => $v): ?>
                <option value="<?= h($k); ?>"
                        data-icon="<?= h($v['icon']); ?>"
                ><?= h($v['label']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-lg-9 mb-4 entries-actions d-flex flex-column flex-md-row align-items-md-center">
        <div class="entries-action entries-action-scroll form-check-inline">
            <input type="checkbox"
                   name="scroll"
                   class="js-toggle-scroll form-check-input"
                   value="1"
                   id="scroll-down-1"
                   <?php if (empty(app('cookie')->get('scrollDisabled'))): ?>checked="checked"<?php endif; ?>
            >
            <label for="scroll-down-1" class="form-check-label"><?= t('Scroll down'); ?></label>
        </div>
        <a href="#"
           class="entries-action js-expand-all"
        ><i class="far fa-plus-square"></i> <?= t('Expand all'); ?></a>
        <a href="#"
           class="entries-action js-collapse-all"
        ><i class="far fa-minus-square"></i> <?= t('Collapse all'); ?></a>
        <a href="#"
           class="entries-action entries-action-remove-all js-remove-all"
           data-group-handle="basic"
           data-confirm-text="<?= t('Are you sure?'); ?>"
        ><i class="fas fa-times-circle"></i> <?= t('Remove all'); ?></a>
    </div>
</div>
<?php dump($basic); ?>
<div class="mb-4">
    <div id="field-types-basic" class="js-sortable" data-entries="<?= h(json_encode($basic)); ?>"></div>
</div>

<div class="row">
    <div class="col-lg-3 mb-4">
        <select class="js-add-entry form-select" data-group-handle="basic">
            <?php foreach ($fieldTypes as $k => $v): ?>
                <option value="<?= h($k); ?>"
                        data-icon="<?= h($v['icon']); ?>"
                ><?= h($v['label']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-lg-9 mb-4 entries-actions d-flex flex-column flex-md-row align-items-md-center">
        <div class="entries-action entries-action-scroll form-check-inline">
            <input type="checkbox"
                   name="scroll"
                   class="js-toggle-scroll form-check-input"
                   value="1"
                   id="scroll-down-2"
                   <?php if (empty(app('cookie')->get('scrollDisabled'))): ?>checked="checked"<?php endif; ?>
            >
            <label for="scroll-down-2" class="form-check-label"><?= t('Scroll down'); ?></label>
        </div>
        <a href="#"
           class="entries-action js-expand-all"
        ><i class="far fa-plus-square"></i> <?= t('Expand all'); ?></a>
        <a href="#"
           class="entries-action js-collapse-all"
        ><i class="far fa-minus-square"></i> <?= t('Collapse all'); ?></a>
        <a href="#"
           class="entries-action entries-action-remove-all js-remove-all"
           data-group-handle="basic"
           data-confirm-text="<?= t('Are you sure?'); ?>"
        ><i class="fas fa-times-circle"></i> <?= t('Remove all'); ?></a>
    </div>
</div>


