<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var BlockBuilder\Block\Dto\CreateBlockDto $config
 * @var BlockBuilder\Environment\Dto\EnvironmentDto $environment
 */
?>

<div class="env-info mb-4">

    <div class="env-info-row">
        <div class="env-info-entry env-info-entry-title">
            <?= t('Current environment'); ?>
        </div>
        <div class="env-info-entry">
            <?= t('Block Builder'); ?>:
            <strong class="env-info-entry-value"><?= h($environment->blockBuilderVersion ?? t('No info')); ?></strong>
        </div>
        <div class="env-info-entry">
            <?= t('Concrete'); ?>:
            <strong class="env-info-entry-value"><?= h($environment->concreteVersion ?? t('No info')); ?></strong>
        </div>
        <div class="env-info-entry">
            <?= t('PHP'); ?>:
            <strong class="env-info-entry-value"><?= h($environment->phpVersion ?? t('No info')); ?></strong>
        </div>
    </div>
    <?php if (isset($config)): ?>
        <div class="env-info-row">
            <div class="env-info-entry env-info-entry-title">
                <?= t('Loaded configuration'); ?>
            </div>
            <div class="env-info-entry">
                <?= t('Block Builder'); ?>:
                <strong class="env-info-entry-value"><?= h($config->blockBuilderVersion ?? t('No info')); ?></strong>
            </div>
            <div class="env-info-entry">
                <?= t('Concrete'); ?>:
                <strong class="env-info-entry-value"><?= h($config->concreteVersion ?? t('No info')); ?></strong>
            </div>
            <div class="env-info-entry">
                <?= t('PHP'); ?>:
                <strong class="env-info-entry-value"><?= h($config->phpVersion ?? t('No info')); ?></strong>
            </div>
        </div>
    <?php endif; ?>

</div>
