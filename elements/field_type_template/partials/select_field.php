<?php defined('C5_EXECUTE') or exit('Access Denied.');

/**
 * @var array $selectFieldTypes
 * @var array $selectFieldListGenerationMethods
 */
?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][displayType]"
               class="form-label"
        ><?= t('Type'); ?></label>
        <select name="<%=context%>[<%=counter%>][displayType]"
                id="<%=context%>[<%=counter%>][displayType]"
                class="form-select"
        >
            <?php foreach ($selectFieldTypes as $k => $v): ?>
                <?php $selected = "<% if (displayType === '" . h($k) . "') { %>selected<% } %>"; ?>
                <option value="<?= h($k); ?>" <?= $selected; ?>>
                    <?= h($v); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][addEmptyOption]"
               class="form-label"
        ><?= t('Add an empty option'); ?></label>
        <select name="<%=context%>[<%=counter%>][addEmptyOption]"
                id="<%=context%>[<%=counter%>][addEmptyOption]"
                class="form-select"
        >
            <?php $selectedNo = "<% if (!addEmptyOption) { %>selected<% } %>"; ?>
            <option value="0" <?= $selectedNo; ?>>
                <?= t('No'); ?>
            </option>
            <?php $selectedYes = "<% if (addEmptyOption) { %>selected<% } %>"; ?>
            <option value="1" <?= $selectedYes; ?>>
                <?= t('Yes'); ?>
            </option>
        </select>
        <div class="form-text">
            <?= t('Works only with the default and enhanced select fields.'); ?>
        </div>
    </div>

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][defaultValue]"
               class="form-label"
        ><?= t('Default value'); ?></label>
        <input type="text"
               id="<%=context%>[<%=counter%>][defaultValue]"
               name="<%=context%>[<%=counter%>][defaultValue]"
               class="form-control"
               value="<%=defaultValue%>"
        >
    </div>

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][listGenerationMethod]"
               class="form-label"
        ><?= t('List generation method'); ?></label>
        <select name="<%=context%>[<%=counter%>][listGenerationMethod]"
                id="<%=context%>[<%=counter%>][listGenerationMethod]"
                class="form-select"
                data-change-select-list-generation-method
        >
            <?php foreach ($selectFieldListGenerationMethods as $k => $v): ?>
                <?php $selected = "<% if (listGenerationMethod === '" . h($k) . "') { %>selected<% } %>"; ?>
                <option value="<?= h($k); ?>" <?= $selected; ?>>
                    <?= h($v); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="<?= '<% if (listGenerationMethod && (listGenerationMethod !== \'basic_list\')) { %>d-none<% } %>'; ?>"
         data-select-list-generation-method="basic_list"
    >
        <div class="mb-4">
            <label for="<%=context%>[<%=counter%>][options]"
                   class="form-label"
            ><?= t('Select options'); ?></label>
            <p class="small text-muted">
                <?= t('Enter each option on a new line'); ?>
                <br>
                <?= t('Use a double colon to specify the key (the value saved in the database) and the value (the displayed text). Keys must start with a letter or number and may contain letters, numbers, underscores, and hyphens.'); ?>
                <code class="bb-code-block">
                    <?= t('no :: Don\'t show'); ?>
                    <br>
                    <?= t('yes :: Show'); ?>
                </code>
            </p>
            <p class="small text-muted">
                <?= t('Although keyless options are supported, using explicit keys is recommended.'); ?>
                <br>
                <?= t('Keyless options receive numeric keys based on their position, starting from 1.'); ?>
                <br>
                <?= t('If an existing block uses keyless options, do not reorder or remove existing options, or insert new options between them. Add new options only at the end of the list.'); ?>
                <br>
                <?= t('Alternatively, assign explicit keys matching the options’ current numeric keys before changing the list.'); ?>
                <code class="bb-code-block">
                    <?= t('Don\'t show'); ?>
                    <br>
                    <?= t('Show'); ?>
                </code>
            </p>
            <textarea name="<%=context%>[<%=counter%>][options]"
                      id="<%=context%>[<%=counter%>][options]"
                      class="form-control"
                      rows="4"
            ><%=options%></textarea>
        </div>
    </div>

    <div class="<?= '<% if (!listGenerationMethod || (listGenerationMethod !== \'custom_code\')) { %>d-none<% } %>'; ?>"
        data-select-list-generation-method="custom_code"
    >
        <label for="<%=context%>[<%=counter%>][customCode]"
               class="form-label"
        ><?= t('Custom code'); ?></label>
        <?php View::element('custom_code_in_option_list', [], 'block_builder'); ?>
        <textarea name="<%=context%>[<%=counter%>][customCode]"
                  id="<%=context%>[<%=counter%>][customCode]"
                  class="form-control"
                  rows="4"
        ><%=customCode%></textarea>
    </div>

</script>
