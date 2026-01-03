<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var array $selectFieldTypes
 * @var array $selectFieldListGenerationMethods
 */
?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="field-entry-hr">

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][selectType]"
               class="form-label"
        ><?= t('Type'); ?></label>
        <select name="<%=context%>[<%=counter%>][selectType]"
                id="<%=context%>[<%=counter%>][selectType]"
                class="form-select"
        >
            <?php foreach ($selectFieldTypes as $k => $v): ?>
                <?php $selected = "<% if (selectType === '" . h($k) . "') { %>selected<% } %>"; ?>
                <option value="<?= h($k); ?>" <?= $selected; ?>>
                    <?= h($v); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][selectAddEmptyOption]"
               class="form-label"
        ><?= t('Add an empty option'); ?></label>
        <select name="<%=context%>[<%=counter%>][selectAddEmptyOption]"
                id="<%=context%>[<%=counter%>][selectAddEmptyOption]"
                class="form-select"
        >
            <?php $selectedNo = "<% if (!selectAddEmptyOption) { %>selected<% } %>"; ?>
            <option value="0" <?= $selectedNo; ?>>
                <?= t('No'); ?>
            </option>
            <?php $selectedYes = "<% if (selectAddEmptyOption) { %>selected<% } %>"; ?>
            <option value="1" <?= $selectedYes; ?>>
                <?= t('Yes'); ?>
            </option>
        </select>
        <div class="form-text">
            <?= t('Works only with the default and enhanced select fields.'); ?>
        </div>
    </div>

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][selectDefaultValue]"
               class="form-label"
        ><?= t('Default value'); ?></label>
        <input type="text"
               id="<%=context%>[<%=counter%>][selectDefaultValue]"
               name="<%=context%>[<%=counter%>][selectDefaultValue]"
               class="form-control"
               value="<%=selectDefaultValue%>"
        >
    </div>

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][selectListGenerationMethod]"
               class="form-label"
        ><?= t('List generation method'); ?></label>
        <select name="<%=context%>[<%=counter%>][selectListGenerationMethod]"
                id="<%=context%>[<%=counter%>][selectListGenerationMethod]"
                class="form-select"
                data-change-select-list-generation-method
        >
            <?php foreach ($selectFieldListGenerationMethods as $k => $v): ?>
                <?php $selected = "<% if (selectListGenerationMethod === '" . h($k) . "') { %>selected<% } %>"; ?>
                <option value="<?= h($k); ?>" <?= $selected; ?>>
                    <?= h($v); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="<?= '<% if (selectListGenerationMethod && (selectListGenerationMethod !== \'basic_list\')) { %>d-none<% } %>'; ?>"
         data-select-list-generation-method="basic_list"
    >
        <div class="mb-4">
            <label for="<%=context%>[<%=counter%>][selectOptions]"
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
            <textarea name="<%=context%>[<%=counter%>][selectOptions]"
                      id="<%=context%>[<%=counter%>][selectOptions]"
                      class="form-control"
                      rows="4"
            ><%=selectOptions%></textarea>
        </div>
    </div>

    <div class="<?= '<% if (!selectListGenerationMethod || (selectListGenerationMethod !== \'custom_code\')) { %>d-none<% } %>'; ?>"
        data-select-list-generation-method="custom_code"
    >
        <label for="<%=context%>[<%=counter%>][selectCustomCode]"
               class="form-label"
        ><?= t('Custom code'); ?></label>
        <?php View::element('custom_code_in_option_list', [], 'block_builder'); ?>
        <textarea name="<%=context%>[<%=counter%>][selectCustomCode]"
                  id="<%=context%>[<%=counter%>][selectCustomCode]"
                  class="form-control"
                  rows="4"
        ><%=selectCustomCode%></textarea>
    </div>

</script>
