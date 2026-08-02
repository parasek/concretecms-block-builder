<?php defined('C5_EXECUTE') or exit('Access Denied.'); ?>

<script type="text/template" data-field-type-partial="<?= h($handle ?? null); ?>">

    <hr class="bb-entry-hr">

    <% const svgIcons = Array.isArray(icons) ? icons : []; %>
    <div
        class="mb-4"
        id="<%-context%>[<%-counter%>][icons]"
        data-svg-icon-definitions
        data-next-icon-index="<%-svgIcons.length%>"
    >
        <div class="form-label"><?= t('SVG icons'); ?> *</div>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-3">
                <thead>
                    <tr>
                        <th scope="col"><?= t('Name'); ?></th>
                        <th scope="col"><?= t('Handle'); ?></th>
                        <th scope="col"><?= t('SVG content'); ?></th>
                        <th class="text-center" scope="col"><?= t('Actions'); ?></th>
                    </tr>
                </thead>
                <tbody data-svg-icon-definition-rows>
                    <% svgIcons.forEach((icon, iconIndex) => { %>
                    <tr data-svg-icon-definition-row>
                        <td>
                            <input
                                class="form-control"
                                id="<%-context%>[<%-counter%>][icons][<%-iconIndex%>][name]"
                                name="<%-context%>[<%-counter%>][icons][<%-iconIndex%>][name]"
                                type="text"
                                value="<%-icon.name || ''%>"
                                maxlength="100"
                            >
                        </td>
                        <td>
                            <input
                                class="form-control"
                                id="<%-context%>[<%-counter%>][icons][<%-iconIndex%>][handle]"
                                name="<%-context%>[<%-counter%>][icons][<%-iconIndex%>][handle]"
                                type="text"
                                value="<%-icon.handle || ''%>"
                                maxlength="50"
                            >
                        </td>
                        <td>
                            <textarea
                                class="form-control font-monospace"
                                id="<%-context%>[<%-counter%>][icons][<%-iconIndex%>][svg]"
                                name="<%-context%>[<%-counter%>][icons][<%-iconIndex%>][svg]"
                                rows="4"
                            ><%-icon.svg || ''%></textarea>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-danger" type="button" data-remove-svg-icon>
                                <i class="fas fa-times" aria-hidden="true"></i>
                                <span class="visually-hidden"><?= t('Remove icon'); ?></span>
                            </button>
                        </td>
                    </tr>
                    <% }); %>
                </tbody>
            </table>
        </div>
        <button class="btn btn-secondary" type="button" data-add-svg-icon>
            <i class="fas fa-plus" aria-hidden="true"></i>
            <?= t('Add icon'); ?>
        </button>

        <template data-svg-icon-definition-template>
            <tr data-svg-icon-definition-row>
                <td>
                    <input
                        class="form-control"
                        id="<%-context%>[<%-counter%>][icons][__ICON_INDEX__][name]"
                        name="<%-context%>[<%-counter%>][icons][__ICON_INDEX__][name]"
                        type="text"
                        maxlength="100"
                    >
                </td>
                <td>
                    <input
                        class="form-control"
                        id="<%-context%>[<%-counter%>][icons][__ICON_INDEX__][handle]"
                        name="<%-context%>[<%-counter%>][icons][__ICON_INDEX__][handle]"
                        type="text"
                        maxlength="50"
                    >
                </td>
                <td>
                    <textarea
                        class="form-control font-monospace"
                        id="<%-context%>[<%-counter%>][icons][__ICON_INDEX__][svg]"
                        name="<%-context%>[<%-counter%>][icons][__ICON_INDEX__][svg]"
                        rows="4"
                    ></textarea>
                </td>
                <td class="text-end">
                    <button class="btn btn-danger" type="button" data-remove-svg-icon>
                        <i class="fas fa-times" aria-hidden="true"></i>
                        <span class="visually-hidden"><?= t('Remove icon'); ?></span>
                    </button>
                </td>
            </tr>
        </template>
        <div class="form-text">
            <?= t('Use unique lowercase handles containing letters and numbers separated by single hyphens or underscores. SVG content is sanitized before block generation.'); ?>
        </div>
    </div>

</script>
