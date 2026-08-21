<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var Concrete\Core\Form\Service\Form $form
 * @var BlockBuilder\Block\Dto\BlockConfigDto $config
 * @var array $fieldsWithError
 * @var array $blockTypeSets
 * @var array $blockIcons
 * @var array $cacheBlockRecordOptions
 * @var array $cacheBlockOutputOptions
 * @var array $cacheBlockOutputOnPostOptions
 * @var array $cacheBlockOutputOnEditModeOptions
 * @var array $cacheBlockOutputForRegisteredUsersOptions
 * @var array $supportSavingNullValuesOptions
 * @var array $ignorePageThemeGridFrameworkContainerOptions
 * @var string $blockIconPreviewPath
 */

$renderControllerPropertyLabel = static function (
    string $fieldHandle,
    string $label,
    string $propertyName,
) use ($form): void {
    ?>
    <div class="bb-controller-property-label">
        <?= $form->label($fieldHandle, $label); ?>
        <code class="bb-controller-property-tag" data-controller-property><?= h($propertyName); ?></code>
    </div>
    <?php
};
?>

<div class="row gx-5">
    <div class="col-xl-6">

        <div data-block-identity>
            <div class="mb-4 <?= h(in_array('blockName', $fieldsWithError) ? 'bb-has-error' : null); ?>">
                <?= $form->label('blockName', t('Block name') . ' *'); ?>
                <?= $form->text('blockName', $config->blockName, [
                    'data-block-name-source' => 'true',
                    'maxlength' => '100',
                ]); ?>
                <div class="form-text"><?= t('Human-readable name, e.g., Example block'); ?></div>
            </div>
            <div class="mb-4 <?= h(in_array('blockHandle', $fieldsWithError) ? 'bb-has-error' : null); ?>">
                <?= $form->label('blockHandle', t('Block handle') . ' *'); ?>
                <div class="bb-handle-input">
                    <?= $form->text('blockHandle', $config->blockHandle, [
                        'data-block-handle' => 'true',
                        'maxlength' => '50',
                    ]); ?>
                    <div
                        class="bb-handle-autogeneration-overlay"
                        data-handle-autogeneration-overlay
                        aria-hidden="true"
                    >
                        <i class="fas fa-circle-notch fa-spin"></i>
                        <?= t('Generating handle from block name'); ?>
                    </div>
                </div>
                <div class="form-text"><?= t('Lowercase letters and underscores only, e.g., example_block'); ?></div>
            </div>
        </div>
        <div class="mb-4 <?= h(in_array('blockTypeSet', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('blockTypeSet', t('Block type set')); ?>
            <?= $form->select('blockTypeSet', $blockTypeSets, $config->blockTypeSet); ?>
        </div>
        <div class="mb-4 <?= h(in_array('blockDescription', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('blockDescription', t('Block description')); ?>
            <?= $form->textarea('blockDescription', $config->blockDescription, ['maxlength' => '100']); ?>
        </div>
        <div class="mb-4 <?= h(in_array('blockWidth', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('blockWidth', t('Block width') . ' *'); ?>
            <div class="input-group">
                <?= $form->text('blockWidth', $config->blockWidth); ?>
                <span class="input-group-text">px</span>
            </div>
        </div>
        <div class="mb-4 <?= h(in_array('blockHeight', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('blockHeight', t('Block height') . ' *'); ?>
            <div class="input-group">
                <?= $form->text('blockHeight', $config->blockHeight); ?>
                <span class="input-group-text">px</span>
            </div>
        </div>
        <div class="mb-4 <?= h(in_array('blockIcon', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('blockIcon', t('Block icon')); ?>
            <div class="d-xxl-flex gap-4">
                <div class="">
                    <div class="bb-block-icon-preview mb-3">
                        <div class="form-label text-center mb-2"><?= t('Preview'); ?></div>
                        <div class="bb-block-icon-preview-image-wrapper text-center">
                            <img src="<?= h($blockIconPreviewPath); ?>"
                                 class="img-fluid"
                                 id="blockIconPreviewImage"
                                 alt="<?= h($config->blockName); ?>"
                            >
                        </div>
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="mb-3">
                        <?= $form->label('blockIcon', t('Choose from existing icons')); ?>
                        <?= $form->select('blockIcon', $blockIcons); ?>
                    </div>
                    <div class="">
                        <?= $form->label('customBlockIcon', t('Upload a custom icon')); ?>
                        <?= $form->file('customBlockIcon'); ?>
                        <div class="form-text"><?= t('Requirements: PNG image, 97 px × 97 px'); ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="col-xl-6">

        <div class="mb-4 <?= h(in_array('cacheBlockRecord', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?php $renderControllerPropertyLabel('cacheBlockRecord', t('Cache block record'), '$btCacheBlockRecord'); ?>
            <?= $form->select('cacheBlockRecord', $cacheBlockRecordOptions, (int) $config->cacheBlockRecord); ?>
            <div class="form-text">
                <?= t('When block caching is enabled, cache the block\'s database record. This can almost always be enabled.'); ?>
            </div>
        </div>
        <div class="mb-4 <?= h(in_array('cacheBlockOutput', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?php $renderControllerPropertyLabel('cacheBlockOutput', t('Cache block output'), '$btCacheBlockOutput'); ?>
            <?= $form->select('cacheBlockOutput', $cacheBlockOutputOptions, (int) $config->cacheBlockOutput); ?>
            <div class="form-text">
                <?= t('When block caching is enabled, cache the rendered output so it can be served without running view() or querying the block database.'); ?>
            </div>
        </div>
        <div class="mb-4 <?= h(in_array('cacheBlockOutputLifetime', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?php $renderControllerPropertyLabel('cacheBlockOutputLifetime', t('Cache block output lifetime'), '$btCacheBlockOutputLifetime'); ?>
            <?= $form->text('cacheBlockOutputLifetime', $config->cacheBlockOutputLifetime); ?>
            <div class="form-text">
                <?= t('Number of seconds before the cached output is refreshed. Use 0 for no time limit.'); ?>
            </div>
        </div>
        <div class="mb-4 <?= h(in_array('cacheBlockOutputOnPost', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?php $renderControllerPropertyLabel('cacheBlockOutputOnPost', t('Cache block output for POST requests'), '$btCacheBlockOutputOnPost'); ?>
            <?= $form->select('cacheBlockOutputOnPost', $cacheBlockOutputOnPostOptions, (int) $config->cacheBlockOutputOnPost); ?>
            <div class="form-text">
                <?= t('Allow cached output for HTTP POST requests. Disable this for blocks that must display POST-specific responses or error messages.'); ?>
            </div>
        </div>
        <div class="mb-4 <?= h(in_array('cacheBlockOutputOnEditMode', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?php $renderControllerPropertyLabel('cacheBlockOutputOnEditMode', t('Cache block output in edit mode'), '$btCacheBlockOutputOnEditMode'); ?>
            <?= $form->select('cacheBlockOutputOnEditMode', $cacheBlockOutputOnEditModeOptions, (int) $config->cacheBlockOutputOnEditMode); ?>
            <div class="form-text">
                <?= t('Allow cached output while the page is in edit mode. Disable this if the block displays edit-mode-specific content or controls.'); ?>
            </div>
        </div>
        <div class="mb-4 <?= h(in_array('cacheBlockOutputForRegisteredUsers', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?php $renderControllerPropertyLabel('cacheBlockOutputForRegisteredUsers', t('Cache block output for registered users'), '$btCacheBlockOutputForRegisteredUsers'); ?>
            <?= $form->select('cacheBlockOutputForRegisteredUsers', $cacheBlockOutputForRegisteredUsersOptions, (int) $config->cacheBlockOutputForRegisteredUsers); ?>
            <div class="form-text">
                <?= t('Continue serving cached block output when the current user is logged in.'); ?>
            </div>
        </div>
        <div class="mb-4 <?= h(in_array('supportSavingNullValues', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?php $renderControllerPropertyLabel('supportSavingNullValues', t('Support saving null values'), '$supportSavingNullValues'); ?>
            <?= $form->select('supportSavingNullValues', $supportSavingNullValuesOptions, (int) $config->supportSavingNullValues); ?>
            <div class="form-text">
                <?= t('Persist NULL values passed to save() or performSave(). When disabled, fields containing NULL are skipped.'); ?>
            </div>
        </div>
        <div class="mb-4 <?= h(in_array('ignorePageThemeGridFrameworkContainer', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?php $renderControllerPropertyLabel('ignorePageThemeGridFrameworkContainer', t('Ignore page theme grid framework container'), '$btIgnorePageThemeGridFrameworkContainer'); ?>
            <?= $form->select('ignorePageThemeGridFrameworkContainer', $ignorePageThemeGridFrameworkContainerOptions, (int) $config->ignorePageThemeGridFrameworkContainer); ?>
            <div class="form-text">
                <?= t('Do not wrap this block in the page theme\'s grid framework container in edit mode.'); ?>
            </div>
        </div>

    </div>
</div>
