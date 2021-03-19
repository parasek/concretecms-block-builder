$(function () {

    Concrete.event.bind('open.block.[[[BLOCK_HANDLE_DASHED]]]', function (e, data) {

        var uniqueID = data.uniqueID;
        var formContainer = $('#form-container-' + uniqueID);
        var entriesContainer = formContainer.find('#entries-' + uniqueID);
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

            var pageSelectors = parentContainer.find('.js-page-selector');
            pageSelectors.each(function (i, item) {
                var inputName = $(item).attr('data-input-name');
                var cID = parseInt($(item).attr('data-collection-id'));
                $(item).concretePageSelector({'inputName': inputName, 'cID': cID});
            });

        }

        function activateFileSelectors(parentContainer) {

            var fileSelectors = parentContainer.find('.js-file-selector');
            fileSelectors.each(function (i, item) {
                var chooseText = $(item).attr('data-choose-text');
                var inputName = $(item).attr('data-input-name');
                var fID = parseInt($(item).attr('data-file-id'));
                $(item).concreteFileSelector({'chooseText': chooseText, 'inputName': inputName, 'filters': [], 'fID': fID});
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

        function activateSpecialFields(container) {

            activateEditors(container);

            activatePageSelectors(container);

            activateFileSelectors(container);

            activateHtmlEditors(container);

            activateDatePickers(container);

        }

        function updateCounter(numberOfEntries) {

            if (numberOfEntries <= maxNumberOfEntries) {
                formContainer.find('.js-number-of-entries').text(numberOfEntries);
            }

            if (numberOfEntries >= maxNumberOfEntries) {
                formContainer.find('.js-add-entry').attr('disabled', true);
                formContainer.find('.js-copy-last-entry').attr('disabled', true);
                formContainer.find('.js-duplicate-entry').attr('disabled', true);
            } else {
                formContainer.find('.js-add-entry').removeAttr('disabled');
                formContainer.find('.js-copy-last-entry').removeAttr('disabled');
                formContainer.find('.js-duplicate-entry').removeAttr('disabled');
            }

            if (numberOfEntries==0) {
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

        function addEntry(action, sourceEntry = false) {

            position++;

            if (countEntries(entriesContainer) == 0) {
                entriesContainer.html('');
            }

            // Append/prepend entry with default values
            var templateData = [];
            templateData['position'] = position;
            $.each(entryColumnNames, function (key, value) {
                if (sourceEntry) {
                    templateData[value] = sourceEntry.find('[name="entry['+sourceEntry.attr('data-position')+']['+value+']"]').val();
                } else {
                    templateData[value] = '';
                }
            });

            var newEntry = false;

            if (action == 'prepend') {
                entriesContainer.prepend(template(templateData));
                newEntry = entriesContainer.children(':first');
            } else {
                entriesContainer.append(template(templateData));
                newEntry = entriesContainer.children(':last');
            }

            // Activate c5 tools/editors
            activateSpecialFields(newEntry);

            updateCounter(countEntries(entriesContainer));

            // Highlight newly added entry
            newEntry.effect('highlight', {}, 1500);

            // Smooth scroll
            formContainer.closest('.ui-dialog-content').animate({
                scrollTop: formContainer.find('.js-entry[data-position="' + position + '"]').position().top + formContainer.closest('.ui-dialog-content').scrollTop()
            }, 1000);

        }

        function loadEntriesAtStart() {

            // Populate form with existing entries
            if (entries && entries.length) {
                $.each(entries, function (index, item) {
                    entriesContainer.append(template(item));
                });
            } else {
                entriesContainer.append(templateNoEntries());
            }

            // Activate c5 tools/editors
            activateSpecialFields(formContainer);

            updateCounter(countEntries(entriesContainer));

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

                var editorIDs = $(this).closest('.js-entry').find('.js-editor-content');

                editorIDs.each(function (i, item) {

                    var editorID = $(item).attr('id');
                    if (typeof CKEDITOR === 'object' && typeof CKEDITOR.instances[editorID] != 'undefined') {
                        CKEDITOR.instances[editorID].destroy();
                    }

                });

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

            var formContainer = $(this).closest('.js-tab-content');

            formContainer.find('.js-entry-content').show();

            var toggleButtons = formContainer.find('.js-toggle-entry');
            toggleButtons.find('i').removeClass('fa-plus-square-o');
            toggleButtons.find('i').addClass('fa-minus-square-o');
            toggleButtons.attr('data-action', 'collapse');

        });

        // Collapse all entries
        formContainer.on('click', '.js-collapse-all', function (e2) {

            e2.preventDefault();

            var formContainer = $(this).closest('.js-tab-content');

            formContainer.find('.js-entry-content').hide();

            var toggleButtons = formContainer.find('.js-toggle-entry');
            toggleButtons.find('i').removeClass('fa-minus-square-o');
            toggleButtons.find('i').addClass('fa-plus-square-o');
            toggleButtons.attr('data-action', 'expand');

        });

        // Toggle entry
        entriesContainer.on('click', '.js-toggle-entry', function (e2) {

            e2.preventDefault();

            var position = $(this).closest('.js-entry').attr('data-position');

            entriesContainer.find('.js-entry[data-position="' + position + '"] .js-entry-content').toggle();

            if ($(this).attr('data-action') == 'collapse') {

                $(this).find('i').removeClass('fa-minus-square-o');
                $(this).find('i').addClass('fa-plus-square-o');
                $(this).attr('data-action', 'expand');

            } else {

                $(this).find('i').removeClass('fa-plus-square-o');
                $(this).find('i').addClass('fa-minus-square-o');
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

            var linkWrapper = $(this).closest('.js-image-wrapper');
            var showText = linkWrapper.find('.js-toggle-additional-image-fields').attr('data-show-text');
            var hideText = linkWrapper.find('.js-toggle-additional-image-fields').attr('data-hide-text');
            var toggleAdditionalFieldsValue = parseInt(linkWrapper.find('.js-toggle-additional-image-fields-value').val());

            if (toggleAdditionalFieldsValue) {
                linkWrapper.find('.js-additional-image-fields-wrapper').hide();
                linkWrapper.find('.js-toggle-additional-image-fields').removeClass('toggle-additional-image-fields-active');
                linkWrapper.find('.js-toggle-additional-image-fields-value').val(0);
                linkWrapper.find('.js-toggle-additional-image-fields-text').text(showText);
            } else {
                linkWrapper.find('.js-additional-image-fields-wrapper').show();
                linkWrapper.find('.js-toggle-additional-image-fields').addClass('toggle-additional-image-fields-active');
                linkWrapper.find('.js-toggle-additional-image-fields-value').val(1);
                linkWrapper.find('.js-toggle-additional-image-fields-text').text(hideText);

            }

        });

        // Image - toggle override thumbnail
        entriesContainer.on('change', '.js-toggle-override-image-dimensions', function () {

            var linkWrapper = $(this).closest('.js-image-wrapper');
            var checked = linkWrapper.find('.js-toggle-override-image-dimensions').is(':checked');

            if (checked) {
                linkWrapper.find('.js-override-image-dimensions-wrapper').show();
            } else {
                linkWrapper.find('.js-override-image-dimensions-wrapper').hide();
            }

        });

        // Image - toggle override fullscreen-image
        entriesContainer.on('change', '.js-toggle-override-fullscreen-image-dimensions', function () {

            var linkWrapper = $(this).closest('.js-image-wrapper');
            var checked = linkWrapper.find('.js-toggle-override-fullscreen-image-dimensions').is(':checked');

            if (checked) {
                linkWrapper.find('.js-override-fullscreen-image-dimensions-wrapper').show();
            } else {
                linkWrapper.find('.js-override-fullscreen-image-dimensions-wrapper').hide();
            }

        });

    });

});