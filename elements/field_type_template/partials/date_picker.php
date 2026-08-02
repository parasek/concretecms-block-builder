<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="form-label"><?= t('Time selector'); ?></div>
            <div class="form-check">
                <input
                    class="form-check-input"
                    id="<%-context%>[<%-counter%>][attachTimeSelector]"
                    name="<%-context%>[<%-counter%>][attachTimeSelector]"
                    type="checkbox"
                    value="1"
                <% if (attachTimeSelector === true || attachTimeSelector === 1 || attachTimeSelector === '1') { %> checked="checked" <% } %>
                >
                <label
                    class="form-check-label"
                    for="<%-context%>[<%-counter%>][attachTimeSelector]"
                ><?= t('Attach time selector'); ?></label>
            </div>
        </div>
        <div class="col-md-6">
            <label
                class="form-label"
                for="<%-context%>[<%-counter%>][minuteInterval]"
            ><?= t('Minute interval'); ?></label>
            <input
                class="form-control"
                id="<%-context%>[<%-counter%>][minuteInterval]"
                name="<%-context%>[<%-counter%>][minuteInterval]"
                type="number"
                value="<%-minuteInterval%>"
                min="1"
                max="60"
                step="1"
            >
            <div class="form-text">
                <?= t('Enter a positive integer that divides 60 without a remainder. For example, 15 generates 00, 15, 30, and 45.'); ?>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label
                class="form-label"
                for="<%-context%>[<%-counter%>][minDate]"
            ><?= t('Minimum date'); ?></label>
            <input
                class="form-control"
                id="<%-context%>[<%-counter%>][minDate]"
                name="<%-context%>[<%-counter%>][minDate]"
                type="date"
                value="<%-minDate%>"
            >
        </div>
        <div class="col-md-6">
            <label
                class="form-label"
                for="<%-context%>[<%-counter%>][maxDate]"
            ><?= t('Maximum date'); ?></label>
            <input
                class="form-control"
                id="<%-context%>[<%-counter%>][maxDate]"
                name="<%-context%>[<%-counter%>][maxDate]"
                type="date"
                value="<%-maxDate%>"
            >
        </div>
    </div>

    <div class="mb-4">
        <label for="<%-context%>[<%-counter%>][datePattern]"
               class="form-label"
        ><?= t('PHP date pattern'); ?></label>
        <input type="text"
               id="<%-context%>[<%-counter%>][datePattern]"
               name="<%-context%>[<%-counter%>][datePattern]"
               class="form-control"
               value="<%-datePattern%>"
        >
        <div class="form-text">
            <?= t('Leave empty to use Concrete CMS\'s localized date format in the view template.'); ?>
            <br>
            <?= t('Enter a PHP date pattern to customize the date displayed in the view template.'); ?>
            <br>
            <?= t('Check the %sPHP manual%s for supported patterns. Examples: <code>d.m.Y</code>, <code>Y-m-d</code>, or <code>d.m.Y H:i</code>.', '<a href="https://www.php.net/manual/en/datetime.format.php" target="_blank" rel="noopener noreferrer">', '</a>'); ?>
        </div>
    </div>

</script>
