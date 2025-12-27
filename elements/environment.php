<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var BlockBuilder\Environment\Dto\EnvironmentDto $environment
 */
?>

<div class="mb-4">
    <span class="info-item-title me-3"><?= t('Current environment info'); ?></span>
    <span class="small badge rounded-pill bg-secondary"><?= t('Block Builder Version'); ?></span> <?= h($environment->blockBuilderVersion ?? t('No info')); ?>
    <span class="small badge rounded-pill bg-secondary ms-2"><?= t('Concrete Version'); ?></span> <?= h($environment->concreteVersion ?? t('No info')); ?>
    <span class="small badge rounded-pill bg-secondary ms-2"><?= t('PHP Version'); ?></span> <?= h($environment->phpVersion ?? t('No info')); ?>
</div>
