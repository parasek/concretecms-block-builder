<?php

declare(strict_types=1);

// Render real Dashboard templates without booting or changing a CMS installation.
class View
{
    public static function element(string $path, array $data, string $package): void
    {
        extract($data);
        require dirname(__DIR__, 3) . '/elements/' . $path . '.php';
    }
}

require dirname(__DIR__, 2) . '/bootstrap.php';
defined('C5_EXECUTE') or define('C5_EXECUTE', true);

$fieldTypes = (new \BlockBuilder\FieldType\FieldTypeRegistry())->all();
$optionProvider = new \BlockBuilder\Service\Option\FieldTypeOptionProvider();
$selectFieldTypes = $optionProvider->getSingleChoiceTypes();
$selectFieldListGenerationMethods = $optionProvider->getListGenerationMethods();
$selectMultipleFieldTypes = $optionProvider->getMultipleChoiceTypes();
$textAdditionalValidations = $optionProvider->getTextAdditionalValidations();
$filesFromFolderOrders = $optionProvider->getFilesFromFolderOrders();
$config = json_decode(file_get_contents(dirname(__DIR__, 3) . '/predefined_configs/all_fields.json'), true, flags: JSON_THROW_ON_ERROR);
?>
<div id="bbAppBuilder">
    <?php require dirname(__DIR__, 3) . '/elements/field_type_template/field_type_template.php'; ?>
    <?php foreach (['basic', 'entries'] as $context) { ?>
        <div data-tab-content>
            <select data-add-entry data-context="<?= h($context); ?>">
                <?php foreach ($fieldTypes as $fieldType) { ?>
                    <option value="<?= h($fieldType::getFieldType()->value); ?>"
                            data-properties="<?= h(json_encode($fieldType::getProperties())); ?>"
                            data-default-values="<?= h(json_encode($fieldType::getDefaultValues())); ?>"
                            data-icon="<?= h($fieldType::getIcon()); ?>"><?= $fieldType::getLabel(); ?></option>
                <?php } ?>
            </select>
            <div id="bb-field-entries-<?= h($context); ?>" data-entries="<?= h(json_encode(array_values($config[$context]))); ?>"></div>
        </div>
    <?php } ?>
</div>
