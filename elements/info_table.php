<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var BlockBuilder\Block\Dto\BlockConfigDto $config
 * @var BlockBuilder\Environment\Dto\EnvironmentDto $environment
 */
?>

<div class="bb-info mb-4">

    <div class="bb-info-row">
        <div class="bb-info-entry bb-info-entry-title">
            <?= t('Current environment'); ?>
        </div>
        <div class="bb-info-entry">
            <?= t('Block Builder'); ?>:
            <strong class="bb-info-entry-value"><?= h($environment->blockBuilderVersion ?? t('No info')); ?></strong>
        </div>
        <div class="bb-info-entry">
            <?= t('Concrete'); ?>:
            <strong class="bb-info-entry-value"><?= h($environment->concreteVersion ?? t('No info')); ?></strong>
        </div>
        <div class="bb-info-entry">
            <?= t('PHP'); ?>:
            <strong class="bb-info-entry-value"><?= h($environment->phpVersion ?? t('No info')); ?></strong>
        </div>
    </div>

    <?php if (isset($config)): ?>
        <div class="bb-info-row">
            <div class="bb-info-entry bb-info-entry-title">
                <?= t('Loaded configuration'); ?>
            </div>
            <div class="bb-info-entry">
                <?= t('Block Builder'); ?>:
                <strong class="bb-info-entry-value"><?= h($config->blockBuilderVersion ?? t('No info')); ?></strong>
            </div>
            <div class="bb-info-entry">
                <?= t('Concrete'); ?>:
                <strong class="bb-info-entry-value"><?= h($config->concreteVersion ?? t('No info')); ?></strong>
            </div>
            <div class="bb-info-entry">
                <?= t('PHP'); ?>:
                <strong class="bb-info-entry-value"><?= h($config->phpVersion ?? t('No info')); ?></strong>
            </div>
        </div>
    <?php endif; ?>

</div>
