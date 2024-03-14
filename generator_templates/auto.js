$(function () {

    Concrete.event.bind('open.block.[[[BLOCK_HANDLE_DASHED]]]', function (e, data) {

        var uniqueID = data.uniqueID;
        var formContainer = $('#form-container-' + uniqueID);
        var entriesContainer = formContainer.find('#entries-' + uniqueID);
        var settingsContainer = formContainer.find('#settings-tab-' + uniqueID);
        var maxNumberOfEntries = parseInt(formContainer.find('.js-max-number-of-entries').text());
        var entryColumnNames = JSON.parse(entriesContainer.attr('data-column-names'));
        var entries = JSON.parse(entriesContainer.attr('data-entries'));
        var position = entries != null ? entries.length : 0;
        var template = _.template(formContainer.find('.js-entry-template').html());
        var templateNoEntries = _.template(formContainer.find('.js-template-no-entries').html());

        function activateEditors(parentContainer) {

            var editors = parentContainer.find('.js-editor-content');
            editors.each(function (i, item) {
                activateEditor(item);
            });

        }

        function activatePageSelectors(parentContainer) {

            var pageSelectors = parentContainer.find('[data-concrete-page-input="js-page-selector"]');
            pageSelectors.each(function (i, item) {
                Concrete.Vue.activateContext('cms', function (Vue, config) {
                    new Vue({
                        el: item,
                        components: config.components
                    })
                })
            });

        }

        function activateFileSelectors(parentContainer) {

            var fileSelectors = parentContainer.find('[data-concrete-file-input="js-file-selector"]');
            fileSelectors.each(function (i, item) {
                Concrete.Vue.activateContext('cms', function (Vue, config) {
                    new Vue({
                        el: item,
                        components: config.components
                    })
                })
            });

        }

        function activateExpressEntrySelectors(parentContainer) {

            var expressEntrySelectors = parentContainer.find('[data-concrete-express-entry-input="js-express-entry-selector"]');
            expressEntrySelectors.each(function (i, item) {
                Concrete.Vue.activateContext('cms', function (Vue, config) {
                    new Vue({
                        el: item,
                        components: config.components
                    })
                })
            });

        }

        function activateHtmlEditors(parentContainer) {

            var htmlEditors = parentContainer.find('.js-html-editor');
            htmlEditors.each(function (i, item) {
                var htmlEditorEntry = ace.edit($(item).attr('id'));
                htmlEditorEntry.setTheme('ace/theme/eclipse');
                htmlEditorEntry.getSession().setMode('ace/mode/html');
                htmlEditorEntry.getSession().on('change', function () {
                    $(item).next().val(htmlEditorEntry.getValue());
                });
            });

        }

        function activateDatePickers(parentContainer) {

            var datePickers = parentContainer.find('.js-entry-date-displayed');
            datePickers.each(function (i, item) {
                var position = $(item).attr('data-position');
                var dateFormat = $(item).attr('data-date-format');
                var targetField = $(item).attr('data-target-field');
                $(item).datepicker({
                    dateFormat: dateFormat,
                    altFormat: 'yy-mm-dd',
                    altField: '.js-entry-' + targetField + '-' + position,
                    changeYear: true,
                    showAnim: 'fadeIn',
                    yearRange: 'c-100:c+10',
                    onClose: function (dateText, inst) {
                        if (!dateText) {
                            $(inst.settings.altField).val('');
                        }
                    }
                });
            });

        }

        function activateColorPickers(parentContainer) {

            var colorPickers = parentContainer.find('.js-color-picker');
            colorPickers.each(function (i, item) {
                var value = $(item).val();
                var cancelText = $(item).attr('data-cancel-text');
                var chooseText = $(item).attr('data-choose-text');
                var togglePaletteMoreText = $(item).attr('data-toggle-palette-more-text');
                var togglePaletteLessText = $(item).attr('data-toggle-palette-less-text');
                var noColorSelectedText = $(item).attr('data-no-color-selected-text');
                var clearText = $(item).attr('data-clear-text');
                $(item).spectrum({
                    'value': value,
                    'type': 'color',
                    'className': 'ccm-widget-colorpicker',
                    'showInitial': true,
                    'showInput': true,
                    'allowEmpty': true,
                    'cancelText': cancelText,
                    'chooseText': chooseText,
                    'togglePaletteMoreText': togglePaletteMoreText,
                    'togglePaletteLessText': togglePaletteLessText,
                    'noColorSelectedText': noColorSelectedText,
                    'preferredFormat': 'rgb',
                    'showAlpha': true,
                    'clearText': clearText,
                    'appendTo': '.ui-dialog'
                });
            });

        }

        function activateEnhancedSelectFields(parentContainer) {

            var fields = parentContainer.find('.js-enhanced-select');
            fields.each(function (i, item) {
                if ($(item).attr('multiple')) {
                    $(item).selectpicker({
                        liveSearch: true,
                        actionsBox: true,
                        width: 'auto',
                        allowClear: true,
                        title: item.title,
                        noneSelectedText: item.dataset.noneSelectedText,
                        noneResultsText: item.dataset.noneResultsText,
                        selectAllText: item.dataset.selectAllText,
                        deselectAllText: item.dataset.deselectAllText,
                    });
                } else {
                    $(item).selectpicker({
                        liveSearch: true,
                        width: 'auto',
                        allowClear: true,
                        title: item.title,
                        noneSelectedText: item.dataset.noneSelectedText,
                        noneResultsText: item.dataset.noneResultsText,
                        deselectAllText: item.dataset.deselectAllText,
                    });
                }
            });

        }

        function activateSpecialFields(container) {

            activateEditors(container);

            activatePageSelectors(container);

            activateFileSelectors(container);

            activateExpressEntrySelectors(container);

            activateHtmlEditors(container);

            activateDatePickers(container);

            activateColorPickers(container);

            activateEnhancedSelectFields(container);

        }

        function updateCounter(numberOfEntries) {

            if (numberOfEntries <= maxNumberOfEntries) {
                formContainer.find('.js-number-of-entries').text(numberOfEntries);
            }

            if (numberOfEntries >= maxNumberOfEntries) {
                formContainer.find('.js-add-entry').attr('disabled', true);
                formContainer.find('.js-copy-last-entry').attr('disabled', true);
                formContainer.find('.js-duplicate-entry').attr('disabled', true);
                formContainer.find('.js-duplicate-entry-and-add-at-the-end').attr('disabled', true);
            } else {
                formContainer.find('.js-add-entry').removeAttr('disabled');
                formContainer.find('.js-copy-last-entry').removeAttr('disabled');
                formContainer.find('.js-duplicate-entry').removeAttr('disabled');
                formContainer.find('.js-duplicate-entry-and-add-at-the-end').removeAttr('disabled');
            }

            if (numberOfEntries == 0) {
                formContainer.find('.js-copy-last-entry').attr('disabled', true);
            }

        }

        function countEntries(parentContainer) {

            var numberOfEntries = parentContainer.children().length;

            var firstElement = parentContainer.children().first();

            if (firstElement.hasClass('js-alert')) {
                numberOfEntries -= 1;
            }

            return numberOfEntries;

        }

        function addEntry(actionOrIndex, sourceEntry = false) {

            position++;

            if (countEntries(entriesContainer) == 0) {
                entriesContainer.html('');
            }

            // Append/prepend entry with default values
            var templateData = [];
            templateData['position'] = position;
            templateData['keepAddedEntryCollapsed'] = formContainer.find('.js-keep-added-entry-collapsed').is(':checked');
            $.each(entryColumnNames, function (key, value) {
                if (sourceEntry) {
                    var pageTypeComposerFormLayoutSetControlID = sourceEntry.closest('[data-page-type-composer-form-layout-set-control-id]').attr('data-page-type-composer-form-layout-set-control-id');
                    // Fields named as array (square brackets at the end) like: name="entry[something][1][]
                    // Checkbox list, Select with multiple attribute (default and enhanced)
                    // Those should be checked before "standard" fields
                    if (pageTypeComposerFormLayoutSetControlID) {
                        var sourceEntryElement = sourceEntry.find('[name="ptComposer[' + pageTypeComposerFormLayoutSetControlID + '][entry][' + sourceEntry.attr('data-position') + '][' + value + '][]"]');
                    } else {
                        var sourceEntryElement = sourceEntry.find('[name="entry[' + sourceEntry.attr('data-position') + '][' + value + '][]"]');
                    }
                    // "Standard" fields
                    if (!sourceEntryElement.length) {
                        if (pageTypeComposerFormLayoutSetControlID) {
                            var sourceEntryElement = sourceEntry.find('[name="ptComposer[' + pageTypeComposerFormLayoutSetControlID + '][entry][' + sourceEntry.attr('data-position') + '][' + value + ']"]');
                        } else {
                            var sourceEntryElement = sourceEntry.find('[name="entry[' + sourceEntry.attr('data-position') + '][' + value + ']"]');
                        }
                    }
                    if (sourceEntryElement.length > 1) {
                        // Checkbox list (Multiple choice)
                        var selectedCheckboxes = [];
                        sourceEntryElement.each(function(index, element) {
                            if ($(element).is(':checked')) {
                                selectedCheckboxes.push($(element).val());
                            }
                        });
                        templateData[value] = selectedCheckboxes.join('|');
                    } else if (sourceEntryElement.attr('type') == 'radio') {
                        // Radio list (Single choice)
                        templateData[value] = sourceEntryElement.filter(':checked').val();
                    } else if (sourceEntryElement.prop('tagName') != undefined && sourceEntryElement.prop('tagName').toLowerCase() == 'select' && sourceEntryElement.attr('multiple')) {
                        // Select with multiple attribute (default and enhanced)
                        templateData[value] = sourceEntryElement.val().join('|');
                    } else {
                        // Rest of fields
                        templateData[value] = sourceEntryElement.val();
                    }
                } else {
                    templateData[value] = '';
                }
            });

            var newEntry = false;

            if (actionOrIndex == 'prepend') {
                entriesContainer.prepend(template(templateData));
                newEntry = entriesContainer.children(':first');
            } else if (actionOrIndex == 'append') {
                entriesContainer.append(template(templateData));
                newEntry = entriesContainer.children(':last');
            } else {
                sourceEntry.after(template(templateData));
                newEntry = entriesContainer.children('.js-entry').eq((parseInt(actionOrIndex) + 1));
            }

            // Activate c5 tools/editors
            activateSpecialFields(newEntry);

            updateCounter(countEntries(entriesContainer));

            // Smooth scroll
            var disableSmoothScroll = formContainer.find('.js-disable-smooth-scroll').is(':checked');
            if (!disableSmoothScroll) {
                formContainer.closest('.ui-dialog-content').animate({
                    scrollTop: formContainer.find('.js-entry[data-position="' + position + '"]').position().top + formContainer.closest('.ui-dialog-content').scrollTop() - formContainer.closest('.ui-dialog').find('.ui-dialog-titlebar').outerHeight()
                }, 1000);
            }

        }

        function loadEntriesAtStart() {

            // Populate form with existing entries
            if (entries && entries.length) {
                $.each(entries, function (index, item) {
                    item['keepAddedEntryCollapsed'] = false;
                    entriesContainer.append(template(item));
                });
            } else {
                entriesContainer.append(templateNoEntries());
            }

            // Activate c5 tools/editors
            activateSpecialFields(formContainer);

            updateCounter(countEntries(entriesContainer));

        }

        function destroyEditors(editorIDs) {

            editorIDs.each(function (i, item) {

                var editorID = $(item).attr('id');
                if (typeof CKEDITOR === 'object' && typeof CKEDITOR.instances[editorID] != 'undefined') {
                    CKEDITOR.instances[editorID].destroy();
                }

            });

        }

        loadEntriesAtStart();

        // Add entry
        formContainer.on('click', '.js-add-entry', function (e) {

            addEntry($(this).attr('data-action'));

        });

        // Duplicate entry
        entriesContainer.on('click', '.js-duplicate-entry', function (e2) {

            e2.preventDefault();

            var sourceEntry = $(this).closest('.js-entry');
            var index = sourceEntry.index();

            addEntry(index, sourceEntry);

        });

        // Duplicate entry and add at the end
        entriesContainer.on('click', '.js-duplicate-entry-and-add-at-the-end', function (e2) {

            e2.preventDefault();

            var sourceEntry = $(this).closest('.js-entry');

            addEntry('append', sourceEntry);

        });

        // Copy last entry
        formContainer.on('click', '.js-copy-last-entry', function (e2) {

            e2.preventDefault();

            var sourceEntry = entriesContainer.children(':last');

            addEntry('append', sourceEntry);

        });

        // Delete entry
        entriesContainer.on('click', '.js-remove-entry', function (e2) {

            e2.preventDefault();

            var dataConfirmText = $(this).attr('data-confirm-text');

            var confirmQuestion = confirm(dataConfirmText);

            if (confirmQuestion == true) {

                destroyEditors($(this).closest('.js-entry').find('.js-editor-content'));

                $(this).closest('.js-entry').remove();

                if (countEntries(entriesContainer) == 0) {

                    entriesContainer.append(templateNoEntries());

                }

                updateCounter(countEntries(entriesContainer));

            }

        });

        // Change header title on input change
        entriesContainer.on('input', '.js-entry-title-source', function () {

            var title = $(this).val();

            if (!title) {
                title = '#' + $(this).closest('.js-entry').attr('data-position');
            }

            $(this).closest('.js-entry')
                .find('.js-entry-title')
                .text(title);

        });

        // Expand all entries
        formContainer.on('click', '.js-expand-all', function (e2) {

            e2.preventDefault();

            var formContainer = $(this).closest('.js-tab-pane');

            formContainer.find('.js-entry-content').show();

            var toggleButtons = formContainer.find('.js-toggle-entry');
            toggleButtons.find('i').removeClass('fa-plus-square');
            toggleButtons.find('i').addClass('fa-minus-square');
            toggleButtons.attr('data-action', 'collapse');

        });

        // Collapse all entries
        formContainer.on('click', '.js-collapse-all', function (e2) {

            e2.preventDefault();

            var formContainer = $(this).closest('.js-tab-pane');

            formContainer.find('.js-entry-content').hide();

            var toggleButtons = formContainer.find('.js-toggle-entry');
            toggleButtons.find('i').removeClass('fa-minus-square');
            toggleButtons.find('i').addClass('fa-plus-square');
            toggleButtons.attr('data-action', 'expand');

        });

        // Remove all entries
        formContainer.on('click', '.js-remove-all', function (e2) {

            e2.preventDefault();

            var dataConfirmText = $(this).attr('data-confirm-text');

            var confirmQuestion = confirm(dataConfirmText);

            if (confirmQuestion == true) {

                destroyEditors(entriesContainer.find('.js-editor-content'));

                entriesContainer.html('');

                entriesContainer.append(templateNoEntries());

                updateCounter(countEntries(entriesContainer));

            }

        });

        // Toggle entry
        entriesContainer.on('click', '.js-toggle-entry', function (e2) {

            e2.preventDefault();

            var position = $(this).closest('.js-entry').attr('data-position');

            entriesContainer.find('.js-entry[data-position="' + position + '"] .js-entry-content').toggle();

            if ($(this).attr('data-action') == 'collapse') {

                $(this).find('i').removeClass('fa-minus-square');
                $(this).find('i').addClass('fa-plus-square');
                $(this).attr('data-action', 'expand');

            } else {

                $(this).find('i').removeClass('fa-plus-square');
                $(this).find('i').addClass('fa-minus-square');
                $(this).attr('data-action', 'collapse');

            }

        });

        // Make entries sortable
        entriesContainer.sortable({
            handle: '.js-move-entry',
            cursor: 'move',
            stop: function (event, ui) {
                $('.sortable').removeClass('hover');
            },
            over: function (event, ui) {
                $('.ui-sortable-placeholder').parents('.sortable').addClass('hover');
            },
            out: function (event, ui) {
                $('.ui-sortable-placeholder').parents('.sortable').removeClass('hover');
            },
            change: function (event, ui) {
                $('.ui-sortable-placeholder').css({
                    visibility: 'visible',
                    background: '#eee'
                });
            }
        });

        // Change external link protocol
        entriesContainer.on('keyup change', '.js-external-link-url', function (e) {

            var url = $(this).val();

            if (url.indexOf('https://') == 0) {
                $(this).val(url.substring(8));
                $(this).parent().closest('.row').find('.js-external-link-protocol').val(url.substring(0, 8));
            } else if (url.indexOf('http://') == 0) {
                $(this).val(url.substring(7));
                $(this).parent().closest('.row').find('.js-external-link-protocol').val(url.substring(0, 7));
            }

        });

        // Link - change type
        entriesContainer.on('change', '.js-link-type', function () {

            var linkWrapper = $(this).closest('.js-link-wrapper');
            var linkType = linkWrapper.find('.js-link-type').val();
            var toggleAdditionalFieldsValue = parseInt(linkWrapper.find('.js-toggle-additional-fields-value').val());

            linkWrapper.find('.js-toggle-additional-fields').hide();
            linkWrapper.find('.js-link-type-wrapper').hide();
            linkWrapper.find('.js-additional-fields-wrapper').hide();

            if (linkType != 0) {
                linkWrapper.find('.js-toggle-additional-fields').show();
                linkWrapper.find('.js-link-type-wrapper-' + linkType).show();
                if (toggleAdditionalFieldsValue == 1) {
                    linkWrapper.find('.js-additional-fields-wrapper').show();
                }
            }

        });

        // Link - Toggle additional fields
        entriesContainer.on('click', '.js-toggle-additional-fields', function () {

            var linkWrapper = $(this).closest('.js-link-wrapper');
            var showText = linkWrapper.find('.js-toggle-additional-fields').attr('data-show-text');
            var hideText = linkWrapper.find('.js-toggle-additional-fields').attr('data-hide-text');
            var toggleAdditionalFieldsValue = parseInt(linkWrapper.find('.js-toggle-additional-fields-value').val());

            if (toggleAdditionalFieldsValue) {
                linkWrapper.find('.js-additional-fields-wrapper').hide();
                linkWrapper.find('.js-toggle-additional-fields').removeClass('toggle-additional-fields-active');
                linkWrapper.find('.js-toggle-additional-fields-value').val(0);
                linkWrapper.find('.js-toggle-additional-fields-text').text(showText);
            } else {
                linkWrapper.find('.js-additional-fields-wrapper').show();
                linkWrapper.find('.js-toggle-additional-fields').addClass('toggle-additional-fields-active');
                linkWrapper.find('.js-toggle-additional-fields-value').val(1);
                linkWrapper.find('.js-toggle-additional-fields-text').text(hideText);

            }

        });

        // Image - Toggle additional fields
        entriesContainer.on('click', '.js-toggle-additional-image-fields', function () {

            var wrapper = $(this).closest('.js-image-wrapper');
            var showText = wrapper.find('.js-toggle-additional-image-fields').attr('data-show-text');
            var hideText = wrapper.find('.js-toggle-additional-image-fields').attr('data-hide-text');
            var toggleAdditionalFieldsValue = parseInt(wrapper.find('.js-toggle-additional-image-fields-value').val());

            if (toggleAdditionalFieldsValue) {
                wrapper.find('.js-additional-image-fields-wrapper').hide();
                wrapper.find('.js-toggle-additional-image-fields').removeClass('toggle-additional-image-fields-active');
                wrapper.find('.js-toggle-additional-image-fields-value').val(0);
                wrapper.find('.js-toggle-additional-image-fields-text').text(showText);
            } else {
                wrapper.find('.js-additional-image-fields-wrapper').show();
                wrapper.find('.js-toggle-additional-image-fields').addClass('toggle-additional-image-fields-active');
                wrapper.find('.js-toggle-additional-image-fields-value').val(1);
                wrapper.find('.js-toggle-additional-image-fields-text').text(hideText);

            }

        });

        // Image - toggle override thumbnail
        entriesContainer.on('change', '.js-toggle-override-image-dimensions', function () {

            var wrapper = $(this).closest('.js-image-wrapper');
            var checked = wrapper.find('.js-toggle-override-image-dimensions').is(':checked');

            if (checked) {
                wrapper.find('.js-override-image-dimensions-wrapper').show();
                $(this).val(1); // fix for duplicate entry bug
            } else {
                wrapper.find('.js-override-image-dimensions-wrapper').hide();
                $(this).val(0); // fix for duplicate entry bug
            }

        });

        // Image - toggle override fullscreen-image
        entriesContainer.on('change', '.js-toggle-override-fullscreen-image-dimensions', function () {

            var wrapper = $(this).closest('.js-image-wrapper');
            var checked = wrapper.find('.js-toggle-override-fullscreen-image-dimensions').is(':checked');

            if (checked) {
                wrapper.find('.js-override-fullscreen-image-dimensions-wrapper').show();
                $(this).val(1); // fix for duplicate entry bug
            } else {
                wrapper.find('.js-override-fullscreen-image-dimensions-wrapper').hide();
                $(this).val(0); // fix for duplicate entry bug
            }

        });

        // Settings / Repeatable image - toggle override thumbnail
        settingsContainer.on('change', '.js-toggle-override-all-dimensions', function () {

            var wrapper = $(this).closest('.js-image-settings-wrapper');
            var checked = wrapper.find('.js-toggle-override-all-dimensions').is(':checked');

            if (checked) {
                wrapper.find('.js-override-all-dimensions-wrapper').show();
            } else {
                wrapper.find('.js-override-all-dimensions-wrapper').hide();
            }

        });

        // Settings / Repeatable image - toggle override fullscreen
        settingsContainer.on('change', '.js-toggle-override-all-fullscreen-dimensions', function () {

            var wrapper = $(this).closest('.js-fullscreen-image-settings-wrapper');
            var checked = wrapper.find('.js-toggle-override-all-fullscreen-dimensions').is(':checked');

            if (checked) {
                wrapper.find('.js-override-all-fullscreen-dimensions-wrapper').show();
            } else {
                wrapper.find('.js-override-all-fullscreen-dimensions-wrapper').hide();
            }

        });

    });

});
