<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="form-check mb-4">
        <input
            class="form-check-input"
            id="<%=context%>[<%=counter%>][displayZeroValue]"
            name="<%=context%>[<%=counter%>][displayZeroValue]"
            type="checkbox"
            value="1"
        <% if (displayZeroValue === true || displayZeroValue === 1 || displayZeroValue === '1') { %> checked="checked" <% } %>
        >
        <label
            class="form-check-label"
            for="<%=context%>[<%=counter%>][displayZeroValue]"
        ><?= t('Display zero ("0") in the view template'); ?></label>
    </div>

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][size]"
               class="form-label"
        ><?= t('Size'); ?></label>
        <input type="text"
               id="<%=context%>[<%=counter%>][size]"
               name="<%=context%>[<%=counter%>][size]"
               class="form-control"
               value="<%=size%>"
        >
        <div class="form-text">
            <?= t('Size of decimal field in MySQL table.'); ?>
            <br>
            <?= t('Value "10.2" means that the database field can store 8 digits for the integer part and 2 digits for the fractional part.'); ?>
            <br>
            <?= t('If you want to store integers in the database, write "0" after the dot ("8.0" or similar).'); ?>
            <br>
            <?= t('If you want to store standard money values in the database, write "2" after the dot ("10.2" or similar).'); ?>
            <br>
            <?= t('Warning: Changing this value after the block is installed can lead to data loss. Proceed with caution.'); ?>
        </div>
    </div>

    <div class="row">

        <div class="col-lg-6">

            <p class="text-body"><strong><?= t('Accepted value'); ?></strong></p>

            <div class="mb-4">
                <label for="<%=context%>[<%=counter%>][step]"
                       class="form-label"
                ><?= t('Step'); ?></label>
                <input type="text"
                       id="<%=context%>[<%=counter%>][step]"
                       name="<%=context%>[<%=counter%>][step]"
                       class="form-control"
                       value="<%=step%>"
                >
                <div class="form-text"><?= t('Value "1" accepts integers; use "0.01" when you want to use a standard money format.'); ?></div>
            </div>

            <div class="mb-4">
                <label for="<%=context%>[<%=counter%>][minimum]"
                       class="form-label"
                ><?= t('Minimum'); ?></label>
                <input type="text"
                       id="<%=context%>[<%=counter%>][minimum]"
                       name="<%=context%>[<%=counter%>][minimum]"
                       class="form-control"
                       value="<%=minimum%>"
                >
            </div>

            <div class="mb-4">
                <label for="<%=context%>[<%=counter%>][maximum]"
                       class="form-label"
                ><?= t('Maximum'); ?></label>
                <input type="text"
                       id="<%=context%>[<%=counter%>][maximum]"
                       name="<%=context%>[<%=counter%>][maximum]"
                       class="form-control"
                       value="<%=maximum%>"
                >
            </div>

        </div>

        <div class="col-lg-6">

            <p class="text-body"><strong><?= t('Displayed value in view template'); ?></strong></p>

            <div class="mb-4">
                <label for="<%=context%>[<%=counter%>][displayedDecimals]"
                       class="form-label"
                ><?= t('Displayed decimals'); ?></label>
                <input type="number"
                       id="<%=context%>[<%=counter%>][displayedDecimals]"
                       name="<%=context%>[<%=counter%>][displayedDecimals]"
                       class="form-control"
                       value="<%=displayedDecimals%>"
                       min="0"
                       step="1"
                >
                <div class="form-text">
                    <?= t('How many decimals to display after the separator.'); ?>
                    <br><?= t('Argument of PHP function number_format()'); ?>
                </div>
            </div>

            <div class="mb-4">
                <label for="<%=context%>[<%=counter%>][displayedDecimalSeparator]"
                       class="form-label"
                ><?= t('Displayed decimal separator'); ?></label>
                <input type="text"
                       id="<%=context%>[<%=counter%>][displayedDecimalSeparator]"
                       name="<%=context%>[<%=counter%>][displayedDecimalSeparator]"
                       class="form-control"
                       value="<%=displayedDecimalSeparator%>"
                >
                <div class="form-text">
                    <?= t('Usually "," (comma) or "." (dot).'); ?>
                    <br><?= t('Argument of PHP function number_format()'); ?>
                </div>
            </div>

            <div class="">
                <label for="<%=context%>[<%=counter%>][displayedThousandsSeparator]"
                       class="form-label"
                ><?= t('Displayed thousands separator'); ?></label>
                <input type="text"
                       id="<%=context%>[<%=counter%>][displayedThousandsSeparator]"
                       name="<%=context%>[<%=counter%>][displayedThousandsSeparator]"
                       class="form-control"
                       value="<%=displayedThousandsSeparator%>"
                >
                <div class="form-text">
                    <?= t('Usually " " (space), "." (dot) or "," (comma). You can also leave it empty.'); ?>
                    <br><?= t('Argument of PHP function number_format()'); ?>
                </div>
            </div>

        </div>
    </div>

</script>
