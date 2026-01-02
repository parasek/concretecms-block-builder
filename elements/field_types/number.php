<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<hr class="field-entry-hr">

<div class="mb-4 <% if (error['numberSize'] !== undefined) { %>has-error<% } %>">
    <label for="<%=groupHandle%>[<%=counter%>][numberSize]"
           class="form-label"
    ><?= t('Size'); ?></label>
    <input type="text"
           id="<%=groupHandle%>[<%=counter%>][numberSize]"
           name="<%=groupHandle%>[<%=counter%>][numberSize]"
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

        <div class="mb-4 <% if (error['numberStep'] !== undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][numberStep]"
                   class="form-label"
            ><?= t('Step'); ?></label>
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][numberStep]"
                   name="<%=groupHandle%>[<%=counter%>][numberStep]"
                   class="form-control"
                   value="<%=numberStep%>"
            >
            <div class="form-text"><?= t('Value "1" will force integers in the HTML field; use "0.01" when you want to use a standard money format.'); ?></div>
        </div>

        <div class="mb-4 <% if (error['numberMin'] !== undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][numberMin]"
                   class="form-label"
            ><?= t('Minimum'); ?></label>
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][numberMin]"
                   name="<%=groupHandle%>[<%=counter%>][numberMin]"
                   class="form-control"
                   value="<%=numberMin%>"
            >
        </div>

        <div class="mb-4 <% if (error['numberMax'] !== undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][numberMax]"
                   class="form-label"
            ><?= t('Maximum'); ?></label>
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][numberMax]"
                   name="<%=groupHandle%>[<%=counter%>][numberMax]"
                   class="form-control"
                   value="<%=numberMax%>"
            >
        </div>

    </div>

    <div class="col-lg-6">

        <p class="text-body"><strong><?= t('Displayed value in view template'); ?></strong></p>

        <div class="mb-4 <% if (error['numberDisplayedDecimals'] !== undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][numberDisplayedDecimals]"
                   class="form-label"
            ><?= t('Displayed decimals'); ?></label>
            <input type="number"
                   id="<%=groupHandle%>[<%=counter%>][numberDisplayedDecimals]"
                   name="<%=groupHandle%>[<%=counter%>][numberDisplayedDecimals]"
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

        <div class="mb-4 <% if (error['numberDisplayedDecimalSeparator'] !== undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][numberDisplayedDecimalSeparator]"
                   class="form-label"
            ><?= t('Displayed decimal separator'); ?></label>
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][numberDisplayedDecimalSeparator]"
                   name="<%=groupHandle%>[<%=counter%>][numberDisplayedDecimalSeparator]"
                   class="form-control"
                   value="<%=numberDisplayedDecimalSeparator%>"
            >
            <div class="form-text">
                <?= t('Usually "," (comma) or "." (dot).'); ?>
                <br><?= t('Argument of PHP function number_format()'); ?>
            </div>
        </div>

        <div class="<% if (error['numberDisplayedThousandsSeparator'] !== undefined) { %>has-error<% } %>">
            <label for="<%=groupHandle%>[<%=counter%>][numberDisplayedThousandsSeparator]"
                   class="form-label"
            ><?= t('Displayed thousands separator'); ?></label>
            <input type="text"
                   id="<%=groupHandle%>[<%=counter%>][numberDisplayedThousandsSeparator]"
                   name="<%=groupHandle%>[<%=counter%>][numberDisplayedThousandsSeparator]"
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

