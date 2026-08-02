<?php defined('C5_EXECUTE') or exit('Access Denied.');

/** @var array $filesFromFolderOrders */
?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <div>
        <label
            class="form-label"
            for="<%-context%>[<%-counter%>][fileOrder]"
        ><?= t('File order'); ?></label>
        <select
            class="form-select"
            id="<%-context%>[<%-counter%>][fileOrder]"
            name="<%-context%>[<%-counter%>][fileOrder]"
        >
            <?php foreach ($filesFromFolderOrders as $value => $label): ?>
                <option
                    value="<?= h($value); ?>"
                    <% if (fileOrder === <?= json_encode($value); ?>) { %> selected="selected" <% } %>
                ><?= h($label); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

</script>
