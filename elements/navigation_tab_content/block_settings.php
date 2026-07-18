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
 * @var array $cacheBlockOutputForRegisteredUsersOptions
 * @var array $supportSavingNullValuesOptions
 * @var array $ignorePageThemeGridFrameworkContainerOptions
 * @var string $blockIconPreviewPath
 */
?>

<div class="row gx-5">
    <div class="col-xl-6">

        <div class="mb-4 <?= h(in_array('blockName', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('blockName', t('Block name') . ' *'); ?>
            <?= $form->text('blockName', $config->blockName, ['maxlength' => '100']); ?>
            <div class="form-text"><?= t('Human-readable name, e.g., Example block'); ?></div>
        </div>
        <div class="mb-4 <?= h(in_array('blockHandle', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('blockHandle', t('Block handle') . ' *'); ?>
            <?= $form->text('blockHandle', $config->blockHandle, ['maxlength' => '50']); ?>
            <div class="form-text"><?= t('Lowercase letters and underscores only, e.g., example_block'); ?></div>
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
        <div class="mb-4 <?= h(in_array('blockTypeSet', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('blockTypeSet', t('Block type set')); ?>
            <?= $form->select('blockTypeSet', $blockTypeSets, $config->blockTypeSet); ?>
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
                        <div class="form-text"><?= t('Requirements: PNG image, 97px x 97px'); ?></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <div class="col-xl-6">

        <div class="mb-4 <?= h(in_array('cacheBlockRecord', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('cacheBlockRecord', t('Cache block record')); ?>
            <?= $form->select('cacheBlockRecord', $cacheBlockRecordOptions, (int) $config->cacheBlockRecord); ?>
        </div>
        <div class="mb-4 <?= h(in_array('cacheBlockOutput', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('cacheBlockOutput', t('Cache block output')); ?>
            <?= $form->select('cacheBlockOutput', $cacheBlockOutputOptions, (int) $config->cacheBlockOutput); ?>
        </div>
        <div class="mb-4 <?= h(in_array('cacheBlockOutputLifetime', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('cacheBlockOutputLifetime', t('Cache block output lifetime')); ?>
            <?= $form->text('cacheBlockOutputLifetime', $config->cacheBlockOutputLifetime); ?>
        </div>
        <div class="mb-4 <?= h(in_array('cacheBlockOutputOnPost', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('cacheBlockOutputOnPost', t('Cache block output on post')); ?>
            <?= $form->select('cacheBlockOutputOnPost', $cacheBlockOutputOnPostOptions, (int) $config->cacheBlockOutputOnPost); ?>
        </div>
        <div class="mb-4 <?= h(in_array('cacheBlockOutputForRegisteredUsers', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('cacheBlockOutputForRegisteredUsers', t('Cache block output for registered users')); ?>
            <?= $form->select('cacheBlockOutputForRegisteredUsers', $cacheBlockOutputForRegisteredUsersOptions, (int) $config->cacheBlockOutputForRegisteredUsers); ?>
        </div>
        <div class="mb-4 <?= h(in_array('supportSavingNullValues', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('supportSavingNullValues', t('Support saving null values')); ?>
            <?= $form->select('supportSavingNullValues', $supportSavingNullValuesOptions, (int) $config->supportSavingNullValues); ?>
        </div>
        <div class="mb-4 <?= h(in_array('ignorePageThemeGridFrameworkContainer', $fieldsWithError) ? 'bb-has-error' : null); ?>">
            <?= $form->label('ignorePageThemeGridFrameworkContainer', t('Ignore page theme grid framework container')); ?>
            <?= $form->select('ignorePageThemeGridFrameworkContainer', $ignorePageThemeGridFrameworkContainerOptions, (int) $config->ignorePageThemeGridFrameworkContainer); ?>
        </div>

    </div>
</div>
