<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var BlockBuilder\Environment\Dto\EnvironmentDto $environment
 * @var BlockBuilder\Block\Dto\CreateBlockDto $config
 */
?>

<div class="mb-4">
    <span class="info-item-title me-3"><?= t('Loaded configuration info'); ?></span>
    <span class="info-item-config small badge rounded-pill"><?= t('Block Builder Version'); ?></span> <?= h($config->blockBuilderVersion ?? t('No info')); ?>
    <span class="info-item-config small badge rounded-pill ms-2"><?= t('Concrete Version'); ?></span> <?= h($config->concreteVersion ?? t('No info')); ?>
    <span class="info-item-config small badge rounded-pill ms-2"><?= t('PHP Version'); ?></span> <?= h($config->phpVersion ?? t('No info')); ?>
    <span class="info-item-config small badge rounded-pill ms-2"><?= t('Created At'); ?></span> <?= h($config->createdAt ?? t('No info')); ?>
</div>
