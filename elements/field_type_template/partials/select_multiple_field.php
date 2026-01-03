<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var array $selectFieldTypes
 * @var array $selectMultipleFieldTypes
 */
?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="field-entry-hr">

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][selectMultipleType]"
               class="form-label"
        ><?= t('Type'); ?></label>
        <select name="<%=context%>[<%=counter%>][selectMultipleType]"
                id="<%=context%>[<%=counter%>][selectMultipleType]"
                class="form-select"
        >
            <?php foreach ($selectMultipleFieldTypes as $k => $v): ?>
                <?php $selected = "<% if (selectMultipleType === '" . h($k) . "') { %>selected<% } %>"; ?>
                <option value="<?= h($k); ?>" <?= $selected; ?>>
                    <?= h($v); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][selectMultipleDefaultValue]"
               class="form-label"
        ><?= t('Default value'); ?></label>
        <input type="text"
               id="<%=context%>[<%=counter%>][selectMultipleDefaultValue]"
               name="<%=context%>[<%=counter%>][selectMultipleDefaultValue]"
               class="form-control"
               value="<%=selectMultipleDefaultValue%>"
        >
        <div class="form-text">
            <?= t('Use the pipe character (|) to separate default values.'); ?>
        </div>
    </div>

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][selectMultipleListGenerationMethod]"
               class="form-label"
        ><?= t('List generation method'); ?></label>
        <select name="<%=context%>[<%=counter%>][selectMultipleListGenerationMethod]"
                id="<%=context%>[<%=counter%>][selectMultipleListGenerationMethod]"
                class="form-select"
                data-change-select-list-generation-method
        >
            <?php foreach ($selectFieldListGenerationMethods as $k => $v): ?>
                <?php $selected = "<% if (selectMultipleListGenerationMethod === '" . h($k) . "') { %>selected<% } %>"; ?>
                <option value="<?= h($k); ?>" <?= $selected; ?>>
                    <?= h($v); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="<?= '<% if (selectMultipleListGenerationMethod && (selectMultipleListGenerationMethod !== \'basic_list\')) { %>d-none<% } %>'; ?>"
         data-select-list-generation-method="basic_list"
    >
        <label for="<%=context%>[<%=counter%>][selectMultipleOptions]"
               class="form-label"
        ><?= t('Select options'); ?></label>
        <p class="small text-muted">
            <?= t('Enter each option on a new line, e.g.'); ?>
            <code class="bb-code-block">
                <?= t('Don\'t show'); ?>
                <br>
                <?= t('Show'); ?>
            </code>
        </p>
        <p class="small text-muted">
            <?= t('You can also use a double colon to specify the key (the value saved in the database, only a-zA-Z0-9_ characters are permitted) and the value (the displayed text), e.g.'); ?>
            <code class="bb-code-block">
                <?= t('no :: Don\'t show'); ?>
                <br>
                <?= t('yes :: Show'); ?>
            </code>
        </p>
        <textarea name="<%=context%>[<%=counter%>][selectMultipleOptions]"
                  id="<%=context%>[<%=counter%>][selectMultipleOptions]"
                  class="form-control"
                  rows="4"
        ><%=selectMultipleOptions%></textarea>
    </div>


    <div class="<?= '<% if (!selectMultipleListGenerationMethod || (selectMultipleListGenerationMethod !== \'custom_code\')) { %>d-none<% } %>'; ?>"
        data-select-list-generation-method="custom_code"
    >
        <label for="<%=context%>[<%=counter%>][selectMultipleCustomCode]"
               class="form-label"
        ><?= t('Custom code'); ?></label>
        <?php View::element('custom_code_in_option_list', [], 'block_builder'); ?>
        <textarea name="<%=context%>[<%=counter%>][selectMultipleCustomCode]"
                  id="<%=context%>[<%=counter%>][selectMultipleCustomCode]"
                  class="form-control"
                  rows="4"
        ><%=selectMultipleCustomCode%></textarea>
    </div>

</script>
