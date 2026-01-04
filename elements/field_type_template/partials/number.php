<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-field-entry-hr">

    <div class="mb-4">
        <label for="<%=context%>[<%=counter%>][numberSize]"
               class="form-label"
        ><?= t('Size'); ?></label>
        <input type="text"
               id="<%=context%>[<%=counter%>][numberSize]"
               name="<%=context%>[<%=counter%>][numberSize]"
               class="form-control"
               value="<%=numberSize%>"
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

            <p class="text-body"><strong><?= t('HTML input attribute'); ?></strong></p>

            <div class="mb-4">
                <label for="<%=context%>[<%=counter%>][numberStep]"
                       class="form-label"
                ><?= t('Step'); ?></label>
                <input type="text"
                       id="<%=context%>[<%=counter%>][numberStep]"
                       name="<%=context%>[<%=counter%>][numberStep]"
                       class="form-control"
                       value="<%=numberStep%>"
                >
                <div class="form-text"><?= t('Value "1" will force integers in the HTML field; use "0.01" when you want to use a standard money format.'); ?></div>
            </div>

            <div class="mb-4">
                <label for="<%=context%>[<%=counter%>][numberMin]"
                       class="form-label"
                ><?= t('Minimum'); ?></label>
                <input type="text"
                       id="<%=context%>[<%=counter%>][numberMin]"
                       name="<%=context%>[<%=counter%>][numberMin]"
                       class="form-control"
                       value="<%=numberMin%>"
                >
            </div>

            <div class="mb-4">
                <label for="<%=context%>[<%=counter%>][numberMax]"
                       class="form-label"
                ><?= t('Maximum'); ?></label>
                <input type="text"
                       id="<%=context%>[<%=counter%>][numberMax]"
                       name="<%=context%>[<%=counter%>][numberMax]"
                       class="form-control"
                       value="<%=numberMax%>"
                >
            </div>

        </div>

        <div class="col-lg-6">

            <p class="text-body"><strong><?= t('Displayed value in view template'); ?></strong></p>

            <div class="mb-4">
                <label for="<%=context%>[<%=counter%>][numberDisplayedDecimals]"
                       class="form-label"
                ><?= t('Displayed decimals'); ?></label>
                <input type="number"
                       id="<%=context%>[<%=counter%>][numberDisplayedDecimals]"
                       name="<%=context%>[<%=counter%>][numberDisplayedDecimals]"
                       class="form-control"
                       value="<%=numberDisplayedDecimals%>"
                       min="0"
                       step="1"
                >
                <div class="form-text">
                    <?= t('How many decimals to display after the separator.'); ?>
                    <br><?= t('Argument of PHP function number_format()'); ?>
                </div>
            </div>

            <div class="mb-4">
                <label for="<%=context%>[<%=counter%>][numberDisplayedDecimalSeparator]"
                       class="form-label"
                ><?= t('Displayed decimal separator'); ?></label>
                <input type="text"
                       id="<%=context%>[<%=counter%>][numberDisplayedDecimalSeparator]"
                       name="<%=context%>[<%=counter%>][numberDisplayedDecimalSeparator]"
                       class="form-control"
                       value="<%=numberDisplayedDecimalSeparator%>"
                >
                <div class="form-text">
                    <?= t('Usually "," (comma) or "." (dot).'); ?>
                    <br><?= t('Argument of PHP function number_format()'); ?>
                </div>
            </div>

            <div class="">
                <label for="<%=context%>[<%=counter%>][numberDisplayedThousandsSeparator]"
                       class="form-label"
                ><?= t('Displayed thousands separator'); ?></label>
                <input type="text"
                       id="<%=context%>[<%=counter%>][numberDisplayedThousandsSeparator]"
                       name="<%=context%>[<%=counter%>][numberDisplayedThousandsSeparator]"
                       class="form-control"
                       value="<%=numberDisplayedThousandsSeparator%>"
                >
                <div class="form-text">
                    <?= t('Usually " " (space), "." (dot) or "," (comma). You can also leave it empty.'); ?>
                    <br><?= t('Argument of PHP function number_format()'); ?>
                </div>
            </div>

        </div>
    </div>

</script>
