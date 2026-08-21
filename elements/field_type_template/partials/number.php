<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="row">
        <div class="col-lg-6 mb-4">
            <label class="form-label" for="<%-context%>[<%-counter%>][defaultValue]"><?= t('Default value'); ?></label>
            <input class="form-control" id="<%-context%>[<%-counter%>][defaultValue]" name="<%-context%>[<%-counter%>][defaultValue]" type="text" value="<%-defaultValue%>">
        </div>
        <div class="col-lg-6 mb-4">
            <label class="form-label" for="<%-context%>[<%-counter%>][placeholder]"><?= t('Placeholder'); ?></label>
            <input class="form-control" id="<%-context%>[<%-counter%>][placeholder]" maxlength="255" name="<%-context%>[<%-counter%>][placeholder]" type="text" value="<%-placeholder%>">
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <label class="form-label" for="<%-context%>[<%-counter%>][prefix]"><?= t('Field prefix'); ?></label>
            <input class="form-control" id="<%-context%>[<%-counter%>][prefix]" maxlength="100" name="<%-context%>[<%-counter%>][prefix]" type="text" value="<%-prefix%>">
            <div class="form-text"><?= t('Text displayed before the input, such as a currency symbol.'); ?></div>
        </div>
        <div class="col-lg-6 mb-4">
            <label class="form-label" for="<%-context%>[<%-counter%>][suffix]"><?= t('Field suffix'); ?></label>
            <input class="form-control" id="<%-context%>[<%-counter%>][suffix]" maxlength="100" name="<%-context%>[<%-counter%>][suffix]" type="text" value="<%-suffix%>">
            <div class="form-text"><?= t('Text displayed after the input, such as a percent sign.'); ?></div>
        </div>
    </div>

    <div class="mb-4">
        <label for="<%-context%>[<%-counter%>][size]"
               class="form-label"
        ><?= t('Database precision and scale'); ?></label>
        <input type="text"
               id="<%-context%>[<%-counter%>][size]"
               name="<%-context%>[<%-counter%>][size]"
               class="form-control"
               value="<%-size%>"
        >
        <div class="form-text">
            <?= t('Precision and scale of the DECIMAL column in the MySQL table.'); ?>
            <br>
            <?= t('A value of "10.2" allows the database column to store 8 integer digits and 2 fractional digits.'); ?>
            <br>
            <?= t('To store integers, use a scale of 0, for example "8.0".'); ?>
            <br>
            <?= t('To store typical currency values, use a scale of 2, for example "10.2".'); ?>
            <br>
            <?= t('Warning: Changing this value after the block is installed can lead to data loss. Proceed with caution.'); ?>
        </div>
    </div>

    <div class="row">

        <div class="col-lg-6">

            <p class="text-body"><strong><?= t('Value constraints'); ?></strong></p>

            <div class="mb-4">
                <label for="<%-context%>[<%-counter%>][step]"
                       class="form-label"
                ><?= t('Step'); ?></label>
                <input type="text"
                       id="<%-context%>[<%-counter%>][step]"
                       name="<%-context%>[<%-counter%>][step]"
                       class="form-control"
                       value="<%-step%>"
                >
                <div class="form-text"><?= t('Use "1" for whole-number increments or "0.01" for currency increments.'); ?></div>
            </div>

            <div class="mb-4">
                <label for="<%-context%>[<%-counter%>][minimum]"
                       class="form-label"
                ><?= t('Minimum'); ?></label>
                <input type="text"
                       id="<%-context%>[<%-counter%>][minimum]"
                       name="<%-context%>[<%-counter%>][minimum]"
                       class="form-control"
                       value="<%-minimum%>"
                >
            </div>

            <div class="mb-4">
                <label for="<%-context%>[<%-counter%>][maximum]"
                       class="form-label"
                ><?= t('Maximum'); ?></label>
                <input type="text"
                       id="<%-context%>[<%-counter%>][maximum]"
                       name="<%-context%>[<%-counter%>][maximum]"
                       class="form-control"
                       value="<%-maximum%>"
                >
            </div>

        </div>

        <div class="col-lg-6">

            <p class="text-body"><strong><?= t('Value displayed in the view template'); ?></strong></p>

            <div class="mb-4">
                <label for="<%-context%>[<%-counter%>][displayedDecimals]"
                       class="form-label"
                ><?= t('Displayed decimals'); ?></label>
                <input type="number"
                       id="<%-context%>[<%-counter%>][displayedDecimals]"
                       name="<%-context%>[<%-counter%>][displayedDecimals]"
                       class="form-control"
                       value="<%-displayedDecimals%>"
                       min="0"
                       step="1"
                >
                <div class="form-text">
                    <?= t('Number of decimal places to display.'); ?>
                    <br><?= t('Argument passed to PHP\'s number_format() function.'); ?>
                </div>
            </div>

            <div class="mb-4">
                <label for="<%-context%>[<%-counter%>][displayedDecimalSeparator]"
                       class="form-label"
                ><?= t('Displayed decimal separator'); ?></label>
                <input type="text"
                       id="<%-context%>[<%-counter%>][displayedDecimalSeparator]"
                       name="<%-context%>[<%-counter%>][displayedDecimalSeparator]"
                       class="form-control"
                       value="<%-displayedDecimalSeparator%>"
                >
                <div class="form-text">
                    <?= t('Usually "," (comma) or "." (dot).'); ?>
                    <br><?= t('Argument passed to PHP\'s number_format() function.'); ?>
                </div>
            </div>

            <div class="mb-4">
                <label for="<%-context%>[<%-counter%>][displayedThousandsSeparator]"
                       class="form-label"
                ><?= t('Displayed thousands separator'); ?></label>
                <input type="text"
                       id="<%-context%>[<%-counter%>][displayedThousandsSeparator]"
                       name="<%-context%>[<%-counter%>][displayedThousandsSeparator]"
                       class="form-control"
                       value="<%-displayedThousandsSeparator%>"
                >
                <div class="form-text">
                    <?= t('Usually " " (space), "." (dot), or "," (comma). You can also leave it empty.'); ?>
                    <br><?= t('Argument passed to PHP\'s number_format() function.'); ?>
                </div>
            </div>

            <div class="form-check mb-4">
                <input
                    class="form-check-input"
                    id="<%-context%>[<%-counter%>][displayZeroValue]"
                    name="<%-context%>[<%-counter%>][displayZeroValue]"
                    type="checkbox"
                    value="1"
                <% if (displayZeroValue === true || displayZeroValue === 1 || displayZeroValue === '1') { %> checked="checked" <% } %>
                >
                <label
                    class="form-check-label"
                    for="<%-context%>[<%-counter%>][displayZeroValue]"
                ><?= t('Display zero ("0") in the view template'); ?></label>
            </div>

        </div>
    </div>

</script>
