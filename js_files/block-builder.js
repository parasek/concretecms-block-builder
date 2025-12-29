$(function () {

    var blockBuilder = (function ($, window, document, undefined) {

        var bbContainer = $('#bbContainer');
        var template = _.template(bbContainer.find('.js-template-entries').html());
        var templateNoEntries = _.template(bbContainer.find('.js-template-no-entries').html());

        var countEntries = function (entriesContainer) {

            var numberOfEntries = entriesContainer.children().length;

            var firstElement = entriesContainer.children().first();

            if (firstElement.hasClass('js-alert')) {
                numberOfEntries -= 1;
            }

            return numberOfEntries;

        };

        // Populate form with existing entries
        var populateFormWithExistingEntries = function (groupHandle) {

            var id = '#field-types-' + groupHandle;

            var entries = JSON.parse($(id).attr('data-entries'));

            if (entries && entries.length) {

                $.each(entries, function (index, item) {

                    const selectedOption = $('.js-add-entry').first().find('option[value="' + item.fieldType + '"]');

                    item.counter = index;
                    item.groupHandle = groupHandle;
                    item.fieldTypeName = selectedOption.text();
                    item.fieldTypeIcon = selectedOption.attr('data-icon');
                    item.error = item.error === undefined ? '' : item.error;

                    // New fields
                    // Those fields don't exist in older .json files.
                    // We would get javascript undefined error when trying to
                    // load .json file created by older version of package.
                    item.selectType = item.selectType === undefined ? 'default_select' : item.selectType;
                    item.selectListGenerationMethod = item.selectListGenerationMethod === undefined ? 'basic_list' : item.selectListGenerationMethod;
                    item.selectAddEmptyOption = (item.selectAddEmptyOption === undefined || !item.selectAddEmptyOption) ? 0 : 1;
                    item.selectDefaultValue = item.selectDefaultValue === undefined ? '' : item.selectDefaultValue;
                    item.selectCustomCode = item.selectCustomCode === undefined ? '' : item.selectCustomCode;
                    item.selectOptions = item.selectOptions === undefined ? '' : item.selectOptions;

                    item.selectMultipleType = item.selectMultipleType === undefined ? 'default_multiselect' : item.selectMultipleType;
                    item.selectMultipleListGenerationMethod = item.selectMultipleListGenerationMethod === undefined ? 'basic_list' : item.selectMultipleListGenerationMethod;
                    item.selectMultipleDefaultValue = item.selectMultipleDefaultValue === undefined ? '' : item.selectMultipleDefaultValue;
                    item.selectMultipleCustomCode = item.selectMultipleCustomCode === undefined ? '' : item.selectMultipleCustomCode;

                    item.wysiwygCustomConfig = item.wysiwygCustomConfig === undefined ? '' : item.wysiwygCustomConfig;

                    // All checkboxes need to be here (because $_POST doesn't have non-checked ones)

                    item.required = (item.required === undefined || item.required === false) ? 0 : 1;
                    item.titleSource = (item.titleSource === undefined || item.titleSource === false) ? 0 : 1;

                    // link_from_sitemap
                    item.linkFromSitemapShowTextField = (item.linkFromSitemapShowTextField === undefined || item.linkFromSitemapShowTextField === false) ? 0 : 1;
                    item.linkFromSitemapShowTitleField = (item.linkFromSitemapShowTitleField === undefined || item.linkFromSitemapShowTitleField === false) ? 0 : 1;
                    item.linkFromSitemapShowEndingField = (item.linkFromSitemapShowEndingField === undefined || item.linkFromSitemapShowEndingField === false) ? 0 : 1;
                    item.linkFromSitemapShowNewWindowField = (item.linkFromSitemapShowNewWindowField === undefined || item.linkFromSitemapShowNewWindowField === false) ? 0 : 1;
                    item.linkFromSitemapShowNoFollowField = (item.linkFromSitemapShowNoFollowField === undefined || item.linkFromSitemapShowNoFollowField === false) ? 0 : 1;

                    // link_from_file_manager
                    item.linkFromFileManagerShowTextField = (item.linkFromFileManagerShowTextField === undefined || item.linkFromFileManagerShowTextField === false) ? 0 : 1;
                    item.linkFromFileManagerShowTitleField = (item.linkFromFileManagerShowTitleField === undefined || item.linkFromFileManagerShowTitleField === false) ? 0 : 1;
                    item.linkFromFileManagerShowEndingField = (item.linkFromFileManagerShowEndingField === undefined || item.linkFromFileManagerShowEndingField === false) ? 0 : 1;
                    item.linkFromFileManagerShowNewWindowField = (item.linkFromFileManagerShowNewWindowField === undefined || item.linkFromFileManagerShowNewWindowField === false) ? 0 : 1;
                    item.linkFromFileManagerShowNoFollowField = (item.linkFromFileManagerShowNoFollowField === undefined || item.linkFromFileManagerShowNoFollowField === false) ? 0 : 1;

                    // external_link
                    item.externalLinkShowTextField = (item.externalLinkShowTextField === undefined || item.externalLinkShowTextField === false) ? 0 : 1;
                    item.externalLinkShowTitleField = (item.externalLinkShowTitleField === undefined || item.externalLinkShowTitleField === false) ? 0 : 1;
                    item.externalLinkShowEndingField = (item.externalLinkShowEndingField === undefined || item.externalLinkShowEndingField === false) ? 0 : 1;
                    item.externalLinkShowNewWindowField = (item.externalLinkShowNewWindowField === undefined || item.externalLinkShowNewWindowField === false) ? 0 : 1;
                    item.externalLinkShowNoFollowField = (item.externalLinkShowNoFollowField === undefined || item.externalLinkShowNoFollowField === false) ? 0 : 1;

                    // image
                    item.imageShowAltTextField = (item.imageShowAltTextField === undefined || item.imageShowAltTextField === false) ? 0 : 1;
                    item.imageCreateFullscreenImage = (item.imageCreateFullscreenImage === undefined || item.imageCreateFullscreenImage === false) ? 0 : 1;
                    item.imageCreateThumbnailImage = (item.imageCreateThumbnailImage === undefined || item.imageCreateThumbnailImage === false) ? 0 : 1;
                    item.imageThumbnailCrop = (item.imageThumbnailCrop === undefined || item.imageThumbnailCrop === false) ? 0 : 1;
                    item.imageFullscreenCrop = (item.imageFullscreenCrop === undefined || item.imageFullscreenCrop === false) ? 0 : 1;
                    item.imageThumbnailEditable = (item.imageThumbnailEditable === undefined || item.imageThumbnailEditable === false) ? 0 : 1;
                    item.imageFullscreenEditable = (item.imageFullscreenEditable === undefined || item.imageFullscreenEditable === false) ? 0 : 1;

                    $(id).append(template(item));

                });
            } else {

                $(id).append(templateNoEntries());

            }

        };

        // Somehow we can't just put it in init, because counting will stop working
        populateFormWithExistingEntries('basic');
        populateFormWithExistingEntries('entries');

        // Add error for basic/entries
        // Need more refinement, it's just a quick fix
        // Need to remove error stuff from other places
        const fieldsWithErrors = JSON.parse(bbContainer.attr('data-fields-with-errors'));
        fieldsWithErrors.forEach(function (fieldWithError) {
            let escapedId = fieldWithError.replace(/[[\]]/g, "\\$&");
            let fieldWithErrorNode = document.querySelector('#' + escapedId);
            if (fieldWithErrorNode) {
                let closestDiv = fieldWithErrorNode.closest('div');
                if (closestDiv) {
                    closestDiv.classList.add('has-error')
                }
                let jsEntry = fieldWithErrorNode.closest('.js-entry');
                if (jsEntry) {
                    jsEntry.classList.add('entry-has-error');
                }
            }
        })

        // Add new entry
        var counterBasic = countEntries($('#field-types-basic'));
        var counterEntries = countEntries($('#field-types-entries'));

        var addEntry = function (e) {

            e.preventDefault();

            var selectedFieldType = $(this);
            var fieldType = selectedFieldType.val();

            if (fieldType) {

                var fieldTypeName = selectedFieldType.find('option:selected').text();
                var fieldTypeIcon = selectedFieldType.find('option:selected').attr('data-icon');
                var groupHandle = selectedFieldType.attr('data-group-handle');
                var entriesContainer = $('#field-types-' + groupHandle);

                var numberOfEntries = countEntries(entriesContainer);

                if (groupHandle == 'basic') {
                    counterBasic++;
                    var counter = counterBasic;
                } else if (groupHandle == 'entries') {
                    counterEntries++;
                    var counter = counterEntries;
                }

                if (numberOfEntries == 0) {
                    entriesContainer.html('');
                }

                var templateData = [];
                templateData['groupHandle'] = groupHandle;
                templateData['counter'] = counter;
                templateData['fieldType'] = fieldType;
                templateData['fieldTypeName'] = fieldTypeName;
                templateData['fieldTypeIcon'] = fieldTypeIcon;
                templateData['error'] = '';

                templateData['label'] = '';
                templateData['handle'] = '';
                templateData['helpText'] = '';
                templateData['required'] = 0;
                templateData['titleSource'] = 0;

                // number
                templateData['numberSize'] = '10.2';
                templateData['numberMin'] = '0';
                templateData['numberMax'] = '99999999.99';
                templateData['numberStep'] = '0.01';
                templateData['numberDisplayedDecimals'] = '2';
                templateData['numberDisplayedDecimalSeparator'] = ',';
                templateData['numberDisplayedThousandsSeparator'] = ' ';

                // textarea
                templateData['textareaHeight'] = '';

                // wysiwyg_editor
                templateData['wysiwygEditorHeight'] = '';
                templateData['wysiwygCustomConfig'] = '';

                // select_field
                templateData['selectListGenerationMethod'] = '';
                templateData['selectAddEmptyOption'] = false;
                templateData['selectDefaultValue'] = '';
                templateData['selectCustomCode'] = '';
                templateData['selectOptions'] = '';
                templateData['selectType'] = '';

                // select_multiple_field
                templateData['selectMultipleListGenerationMethod'] = '';
                templateData['selectMultipleDefaultValue'] = '';
                templateData['selectMultipleCustomCode'] = '';
                templateData['selectMultipleOptions'] = '';
                templateData['selectMultipleType'] = '';

                // link
                templateData['link'] = 0;

                // link_from_sitemap
                templateData['linkFromSitemapShowTextField'] = 0;
                templateData['linkFromSitemapShowTitleField'] = 0;
                templateData['linkFromSitemapShowEndingField'] = 0;
                templateData['linkFromSitemapShowNewWindowField'] = 0;
                templateData['linkFromSitemapShowNoFollowField'] = 0;

                // link_from_file_manager
                templateData['linkFromFileManagerShowTextField'] = 0;
                templateData['linkFromFileManagerShowTitleField'] = 0;
                templateData['linkFromFileManagerShowEndingField'] = 0;
                templateData['linkFromFileManagerShowNewWindowField'] = 0;
                templateData['linkFromFileManagerShowNoFollowField'] = 0;

                // external_link
                templateData['externalLinkShowTextField'] = 0;
                templateData['externalLinkShowTitleField'] = 0;
                templateData['externalLinkShowEndingField'] = 0;
                templateData['externalLinkShowNewWindowField'] = 0;
                templateData['externalLinkShowNoFollowField'] = 0;

                // image
                templateData['imageShowAltTextField'] = 1;
                templateData['imageCreateFullscreenImage'] = 1;
                templateData['imageCreateThumbnailImage'] = 1;

                templateData['imageThumbnailWidth'] = 480;
                templateData['imageThumbnailHeight'] = 270;
                templateData['imageThumbnailCrop'] = 1;
                templateData['imageThumbnailEditable'] = 1;

                templateData['imageFullscreenWidth'] = 1920;
                templateData['imageFullscreenHeight'] = 1080;
                templateData['imageFullscreenCrop'] = 0;
                templateData['imageFullscreenEditable'] = 1;

                // express
                templateData['expressHandle'] = '';

                // file_set
                templateData['fileSetPrefix'] = '';

                // html_editor
                templateData['htmlEditorHeight'] = '';

                // date_picker
                templateData['datePickerPattern'] = 'd.m.Y';

                entriesContainer.append(template(templateData));

                //var newField = entriesContainer.children(':last');
                //newField.effect('highlight', {}, 1500);

                selectedFieldType.val('');

                // Smooth scroll
                if (!$.cookie('scrollDisabled')) {
                    $('html').animate({
                        scrollTop: entriesContainer.find('.js-entry[data-counter="' + counter + '"]').position().top - 50 + entriesContainer.scrollTop()
                    }, 0);
                }

            }

        };

        // Delete entry
        var removeEntry = function (e) {

            e.preventDefault();

            var dataConfirmText = $(this).attr('data-confirm-text');

            var confirmQuestion = confirm(dataConfirmText);

            if (confirmQuestion == true) {

                var entriesContainer = $(this).closest('.js-sortable');

                $(this).closest('.js-entry').remove();

                if (countEntries(entriesContainer) == 0) {

                    entriesContainer.append(templateNoEntries());

                }

            }

        };

        // Toggle entry
        var toggleEntry = function (e) {

            e.preventDefault();

            var counter = $(this).closest('.js-entry').attr('data-counter');

            var entriesContainer = $(this).closest('.js-sortable');

            entriesContainer.find('.js-entry[data-counter="' + counter + '"] .js-entry-content').toggle();

            if ($(this).attr('data-action') == 'collapse') {

                $(this).find('i').removeClass('fa-minus-square');
                $(this).find('i').addClass('fa-plus-square');
                $(this).attr('data-action', 'expand');

            } else {

                $(this).find('i').removeClass('fa-plus-square');
                $(this).find('i').addClass('fa-minus-square');
                $(this).attr('data-action', 'collapse');

            }

        };

        // Toggle scroll
        var toggleScroll = function (e) {

            if (!$(this).is(':checked')) {
                $('.js-toggle-scroll').prop('checked', false);
                $.cookie('scrollDisabled', 1, {expires: 180});
            } else {
                $('.js-toggle-scroll').prop('checked', true);
                $.cookie('scrollDisabled', 1, {expires: -1});
            }

        };

        // Collapse all entries
        var collapseAllEntries = function (e) {

            e.preventDefault();

            var formContainer = $(this).closest('.ccm-tab-content');

            formContainer.find('.js-entry-content').hide();

            var toggleButtons = formContainer.find('.js-toggle-entry');
            toggleButtons.find('i').removeClass('fa-minus-square');
            toggleButtons.find('i').addClass('fa-plus-square');
            toggleButtons.attr('data-action', 'expand');

        };

        // Expand all entries
        var expandAllEntries = function (e) {

            e.preventDefault();

            var formContainer = $(this).closest('.ccm-tab-content');

            formContainer.find('.js-entry-content').show();

            var toggleButtons = formContainer.find('.js-toggle-entry');
            toggleButtons.find('i').removeClass('fa-plus-square');
            toggleButtons.find('i').addClass('fa-minus-square');
            toggleButtons.attr('data-action', 'collapse');

        };

        // Remove all entries
        var removeAllEntries = function (e) {

            e.preventDefault();

            var confirmText = $(this).attr('data-confirm-text');
            var groupHandle = $(this).attr('data-group-handle');
            var entriesContainer = $('#field-types-' + groupHandle);

            var confirmQuestion = confirm(confirmText);

            if (confirmQuestion == true) {

                entriesContainer.html('');

                $(entriesContainer).append(templateNoEntries());

            }

        };

        // Change entry title on input change
        var changeEntryTitle = function (e) {

            var title = $(this).val();

            if (!title) {

                title = '#' + $(this).closest('.js-entry').attr('data-counter');

            }

            $(this).closest('.js-entry')
                .find('.js-entry-title')
                .text(title);

        };

        // Populate fields with translated or untranslated texts
        var populateTranslationFields = function (e) {

            e.preventDefault();

            var replacementType = $(this).attr('data-type');
            var tabHandle = $(this).attr('data-tab-handle');

            var inputs = $('#ccm-tab-content-'+tabHandle).find('input');

            inputs.each(function (i, item) {
                if (replacementType == 'translated') {
                    var newText = $(item).attr('data-translated-text');
                } else {
                    var newText = $(item).attr('data-untranslated-text');
                }
                $(item).val(newText);
            });

        };

        // Use field as title in repeatable entries
        var useFieldAsTitleInRepeatableEntries = function (e) {

            e.preventDefault();

            var clickedCheckbox = $(this);

            var formContainer = clickedCheckbox.closest('.ccm-tab-content');

            var checkboxes = formContainer.find('.js-use-field-as-title-in-repeatable-entries');
            checkboxes.each(function (i, item) {
                if ($(item).attr('name') != clickedCheckbox.attr('name') && clickedCheckbox.is(':checked')) {
                    $(item).prop('checked', false);
                }
            });

        };

        // Create thumbnail image
        var createThumbnailImage = function (e) {

            e.preventDefault();

            var clickedCheckbox = $(this);

            var formContainer = clickedCheckbox.closest('.js-entry-content');

            var optionsWrapper = formContainer.find('.js-image-create-thumbnail-image-wrapper');

            if (clickedCheckbox.is(':checked')) {
                optionsWrapper.show();
            } else {
                optionsWrapper.hide();
            }

        };

        // Create fullscreen image
        var createFullscreenImage = function (e) {

            e.preventDefault();

            var clickedCheckbox = $(this);

            var formContainer = clickedCheckbox.closest('.js-entry-content');

            var optionsWrapper = formContainer.find('.js-image-create-fullscreen-image-wrapper');

            if (clickedCheckbox.is(':checked')) {
                optionsWrapper.show();
            } else {
                optionsWrapper.hide();
            }

        };

        // Change Select List generation method
        var changeSelectListGenerationMethod = function (e) {

            e.preventDefault();

            var selectField = $(this);
            var checkedValue = selectField.val();
            var entryContent = selectField.closest('.js-entry-content');

            var listWrapper = entryContent.find('[data-select-list-generation-method="basic_list"]');
            var customCodeWrapper = entryContent.find('[data-select-list-generation-method="custom_code"]');

            listWrapper.hide();
            customCodeWrapper.hide();

            if (checkedValue === 'custom_code') {
                customCodeWrapper.show();
            } else {
                listWrapper.show();
            }

        };

        // Install block type
        var installBlockType = function (e) {

            e.preventDefault();

            var csrfToken = bbContainer.attr('data-csrf-token');
            var installBlockTypeUrl = bbContainer.attr('data-install-block-type-url');
            var successMessagePart1 = bbContainer.attr('data-install-block-type-success-message-1');
            var successMessagePart2 = bbContainer.attr('data-install-block-type-success-message-2');
            var confirmationMessage = bbContainer.attr('data-confirmation-message');
            var handle = $(this).attr('data-handle');

            var confirmQuestion = confirm(confirmationMessage);

            if (confirmQuestion == true) {

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        csrfToken: csrfToken,
                        handle: handle
                    },
                    url: installBlockTypeUrl
                }).done(function (response) {
                    // This only runs for 2xx status codes
                    alert(successMessagePart1 + '\n' + successMessagePart2);
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    // This runs for 400, 405, 500, etc.
                    var message = 'Oops! Something went wrong...';

                    // Try to parse the JSON response from the server even though it failed
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        message = jqXHR.responseJSON.message;
                    }

                    alert(message);
                });

            }

        };

        // Uninstall block type
        var uninstallBlockType = function (e) {

            e.preventDefault();

            var csrfToken = bbContainer.attr('data-csrf-token');
            var uninstallBlockTypeUrl = bbContainer.attr('data-uninstall-block-type-url');
            var successMessagePart1 = bbContainer.attr('data-uninstall-block-type-success-message-1');
            var successMessagePart2 = bbContainer.attr('data-uninstall-block-type-success-message-2');
            var confirmationMessage = bbContainer.attr('data-confirmation-message');
            var handle = $(this).attr('data-handle');

            var confirmQuestion = confirm(confirmationMessage);

            if (confirmQuestion == true) {

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        csrfToken: csrfToken,
                        handle: handle
                    },
                    url: uninstallBlockTypeUrl
                }).done(function (response) {
                    // This only runs for 2xx status codes
                    alert(successMessagePart1 + '\n' + successMessagePart2);
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    // This runs for 400, 405, 500, etc.
                    var message = 'Oops! Something went wrong...';

                    // Try to parse the JSON response from the server even though it failed
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        message = jqXHR.responseJSON.message;
                    }

                    alert(message);
                });

            }

        };

        // Delete block type folder
        var deleteBlockTypeFolder = function (e) {

            e.preventDefault();

            var csrfToken = bbContainer.attr('data-csrf-token');
            var deleteBlockTypeFolderUrl = bbContainer.attr('data-delete-block-type-folder-url');
            var successMessagePart1 = bbContainer.attr('data-delete-block-type-folder-success-message-1');
            var successMessagePart2 = bbContainer.attr('data-delete-block-type-folder-success-message-2');
            var confirmationMessage = bbContainer.attr('data-confirmation-message');
            var handle = $(this).attr('data-handle');

            var confirmQuestion = confirm(confirmationMessage);

            if (confirmQuestion == true) {

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        csrfToken: csrfToken,
                        handle: handle
                    },
                    url: deleteBlockTypeFolderUrl
                }).done(function (response) {
                    // This only runs for 2xx status codes
                    alert(successMessagePart1 + '\n' + successMessagePart2);
                }).fail(function (jqXHR, textStatus, errorThrown) {
                    // This runs for 400, 405, 500, etc.
                    var message = 'Oops! Something went wrong...';

                    // Try to parse the JSON response from the server even though it failed
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        message = jqXHR.responseJSON.message;
                    }

                    alert(message);
                });

            }

        };

        var initSortable = function () {

            var sortableContainers = bbContainer.find('.js-sortable');

            sortableContainers.each(function (i, item) {
                $(item).sortable({
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
            });

        };

        var initNavigationTabs = function (navContainer) {

            navContainer = $(navContainer);

            var hash = window.location.hash;
            var activeTab;

            // Find active tab (by # in url or first element)
            if (hash) {
                activeTab = hash.substring(1);
                navContainer.find('a').removeClass('navigation-tab-link-active');
                navContainer.find('a[data-tab="' + activeTab + '"]').addClass('navigation-tab-link-active');
            } else {
                activeTab = navContainer.children(':first').find('a').attr('data-tab');
                navContainer.children(':first').find('a').addClass('navigation-tab-link-active');
            }

            // Show content of active tab
            $('#ccm-tab-content-' + activeTab).show();

            // Modify form action attribute, so hash will persist through $_POST
            navContainer.closest('form').attr('action', function (i, val) {
                return val.split('#')[0] + hash;
            });

            // On navigation click
            navContainer.find('a').click(function (e) {

                e.preventDefault();

                var activeTab = $(this).attr('data-tab');

                navContainer.find('a').removeClass('navigation-tab-link-active');
                $(this).addClass('navigation-tab-link-active');
                navContainer.find('a').each(function (i, item) {
                    $('#ccm-tab-content-' + $(item).attr('data-tab')).hide();
                });
                $('#ccm-tab-content-' + activeTab).show();
                window.location.hash = '#' + activeTab;

                navContainer.closest('form').attr('action', function (i, val) {
                    return val.split('#')[0] + window.location.hash;
                });

                return false;

            });
        };

        var bindFunctions = function () {
            bbContainer.on('change', '.js-add-entry', addEntry);
            bbContainer.on('click', '.js-remove-entry', removeEntry);
            bbContainer.on('click', '.js-toggle-entry', toggleEntry);
            bbContainer.on('click', '.js-toggle-scroll', toggleScroll);
            bbContainer.on('click', '.js-expand-all', expandAllEntries);
            bbContainer.on('click', '.js-collapse-all', collapseAllEntries);
            bbContainer.on('click', '.js-remove-all', removeAllEntries);
            bbContainer.on('input', '.js-entry-title-source', changeEntryTitle);
            bbContainer.on('click', '.js-populate-translation-fields', populateTranslationFields);
            bbContainer.on('change', '.js-use-field-as-title-in-repeatable-entries', useFieldAsTitleInRepeatableEntries);
            bbContainer.on('change', '.js-image-create-thumbnail-image', createThumbnailImage);
            bbContainer.on('change', '.js-image-create-fullscreen-image', createFullscreenImage);
            bbContainer.on('change', '.js-change-select-list-generation-method', changeSelectListGenerationMethod);
            $('.alert').on('click', '.js-install-block-type', installBlockType);
            $('.alert').on('click', '.js-uninstall-block-type', uninstallBlockType);
            $('.alert').on('click', '.js-delete-block-type-folder', deleteBlockTypeFolder);
        };

        var init = function () {
            initNavigationTabs('#navigation-tabs');
            initSortable();
            bindFunctions();
        };

        return {
            init: init
        };

    })(jQuery, window, document);

    blockBuilder.init();

});
