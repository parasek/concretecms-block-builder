$(function() {

    var blockBuilder = (function ($, window, document, undefined) {

        var bbContainer       = $('#bb-container');
        var template          = _.template(bbContainer.find('.js-template-entries').html());
        var templateNoEntries = _.template(bbContainer.find('.js-template-no-entries').html());

        var countEntries = function(entriesContainer) {

            var numberOfEntries = entriesContainer.children().length;

            var firstElement = entriesContainer.children().first();

            if (firstElement.hasClass('js-alert')) {
                numberOfEntries -= 1;
            }

            return numberOfEntries;

        };

        // Populate form with existing entries
        var populateFormWithExistingEntries = function(groupHandle) {

            var id = '#field-types-'+groupHandle;

            var entries = JSON.parse($(id).attr('data-entries'));

            if (entries && entries.length) {

                $.each(entries, function (index, item) {

                    item.counter       = index+1;
                    item.groupHandle   = groupHandle;
                    item.fieldTypeName = $('.js-add-entry').first().find('option[value="'+item.fieldType+'"]').text();
                    item.error         = item.error==undefined ? '' : item.error;

                    // All checkboxes need to be here (because $_POST doesn't have non-checked ones)

                    item.required    = item.required==undefined ? 0 : 1;
                    item.titleSource = item.titleSource==undefined ? 0 : 1;

                    // link_from_sitemap
                    item.linkFromSitemapShowTextField   = item.linkFromSitemapShowTextField==undefined ? 0 : 1;
                    item.linkFromSitemapShowTitleField  = item.linkFromSitemapShowTitleField==undefined ? 0 : 1;
                    item.linkFromSitemapShowEndingField = item.linkFromSitemapShowEndingField==undefined ? 0 : 1;

                    // link_from_file_manager
                    item.linkFromFileManagerShowTextField  = item.linkFromFileManagerShowTextField==undefined ? 0 : 1;
                    item.linkFromFileManagerShowTitleField = item.linkFromFileManagerShowTitleField==undefined ? 0 : 1;

                    // external_link
                    item.externalLinkShowTextField  = item.externalLinkShowTextField==undefined ? 0 : 1;
                    item.externalLinkShowTitleField = item.externalLinkShowTitleField==undefined ? 0 : 1;

                    // image
                    item.imageShowAltTextField      = item.imageShowAltTextField==undefined ? 0 : 1;
                    item.imageCreateFullscreenImage = item.imageCreateFullscreenImage==undefined ? 0 : 1;
                    item.imageCreateThumbnailImage  = item.imageCreateThumbnailImage==undefined ? 0 : 1;
                    item.imageThumbnailCrop         = item.imageThumbnailCrop==undefined ? 0 : 1;
                    item.imageFullscreenCrop        = item.imageFullscreenCrop==undefined ? 0 : 1;

                    $(id).append(template(item));

                });
            } else {

                $(id).append(templateNoEntries());

            }

        };

        // Somehow we can't just put it in init, because counting will stop working
        populateFormWithExistingEntries('basic');
        populateFormWithExistingEntries('entries');

        // Add new entry
        var counterBasic   = countEntries($('#field-types-basic'));
        var counterEntries = countEntries($('#field-types-entries'));

        var addEntry = function(e) {

            e.preventDefault();

            var selectedFieldType = $(this);
            var fieldType         = selectedFieldType.val();

            if (fieldType) {

                var fieldTypeName    = selectedFieldType.find('option:selected').text();
                var groupHandle      = selectedFieldType.attr('data-group-handle');
                var entriesContainer = $('#field-types-'+groupHandle);

                var numberOfEntries = countEntries(entriesContainer);

                if (groupHandle=='basic') {
                    counterBasic++;
                    var counter = counterBasic;
                } else if (groupHandle=='entries') {
                    counterEntries++;
                    var counter = counterEntries;
                }

                if (numberOfEntries==0) {
                    entriesContainer.html('');
                }

                var templateData = [];
                templateData['groupHandle']   = groupHandle;
                templateData['counter']       = counter;
                templateData['fieldType']     = fieldType;
                templateData['fieldTypeName'] = fieldTypeName;
                templateData['error']         = '';

                templateData['label']         = '';
                templateData['handle']        = '';
                templateData['helpText']      = '';
                templateData['required']      = 0;
                templateData['titleSource']   = 0;

                // textarea
                templateData['textareaHeight'] = '';

                // wysiwyg_editor
                templateData['wysiwygEditorHeight'] = '';

                // select_field
                templateData['selectOptions'] = '';

                // link_from_sitemap
                templateData['linkFromSitemapShowTextField']   = 0;
                templateData['linkFromSitemapShowTitleField']  = 0;
                templateData['linkFromSitemapShowEndingField'] = 0;

                // link_from_file_manager
                templateData['linkFromFileManagerShowTextField']  = 0;
                templateData['linkFromFileManagerShowTitleField'] = 0;

                // external_link
                templateData['externalLinkShowTextField']  = 0;
                templateData['externalLinkShowTitleField'] = 0;

                // image
                templateData['imageShowAltTextField']      = 0;
                templateData['imageCreateFullscreenImage'] = 1;
                templateData['imageCreateThumbnailImage']  = 1;

                templateData['imageThumbnailWidth']        = 480;
                templateData['imageThumbnailHeight']       = 270;
                templateData['imageThumbnailCrop']         = 1;

                templateData['imageFullscreenWidth']       = 1920;
                templateData['imageFullscreenHeight']      = 1080;
                templateData['imageFullscreenCrop']        = 0;

                // html_editor
                templateData['htmlEditorHeight'] = '';

                // date_picker
                templateData['datePickerPattern'] = 'd.m.Y';

                entriesContainer.append(template(templateData));
                var newField = entriesContainer.children(':last');

                newField.effect('highlight', {}, 1500);

                selectedFieldType.val('');

                // Smooth scroll
                $('html').animate({
                    scrollTop: entriesContainer.find('.js-entry[data-counter="'+counter+'"]').position().top-50 + entriesContainer.scrollTop()
                });

            }

        };

        // Delete entry
        var removeEntry = function(e) {

            e.preventDefault();

            var dataConfirmText = $(this).attr('data-confirm-text');

            var confirmQuestion = confirm(dataConfirmText);

            if (confirmQuestion == true) {

                var entriesContainer = $(this).closest('.js-sortable');

                $(this).closest('.js-entry').remove();

                if (countEntries(entriesContainer)==0) {

                    entriesContainer.append(templateNoEntries());

                }

            }

        };

        // Toggle entry
        var toggleEntry = function(e) {

            e.preventDefault();

            var counter = $(this).closest('.js-entry').attr('data-counter');

            var entriesContainer = $(this).closest('.js-sortable');

            entriesContainer.find('.js-entry[data-counter="'+counter+'"] .js-entry-content').toggle();

            if ($(this).attr('data-action')=='collapse') {

                $(this).find('i').removeClass('fa-minus-square-o');
                $(this).find('i').addClass('fa-plus-square-o');
                $(this).attr('data-action', 'expand');

            } else {

                $(this).find('i').removeClass('fa-plus-square-o');
                $(this).find('i').addClass('fa-minus-square-o');
                $(this).attr('data-action', 'collapse');

            }

        };

        // Collapse all entries
        var collapseAllEntries = function(e) {

            e.preventDefault();

            var formContainer = $(this).closest('.ccm-tab-content');

            formContainer.find('.js-entry-content').hide();

            var toggleButtons = formContainer.find('.js-toggle-entry');
            toggleButtons.find('i').removeClass('fa-minus-square-o');
            toggleButtons.find('i').addClass('fa-plus-square-o');
            toggleButtons.attr('data-action', 'expand');

        };

        // Expand all entries
        var expandAllEntries = function(e) {

            e.preventDefault();

            var formContainer = $(this).closest('.ccm-tab-content');

            formContainer.find('.js-entry-content').show();

            var toggleButtons = formContainer.find('.js-toggle-entry');
            toggleButtons.find('i').removeClass('fa-plus-square-o');
            toggleButtons.find('i').addClass('fa-minus-square-o');
            toggleButtons.attr('data-action', 'collapse');

        };

        // Remove all entries
        var removeAllEntries = function(e) {

            e.preventDefault();

            var confirmText = $(this).attr('data-confirm-text');
            var groupHandle = $(this).attr('data-group-handle');
            var entriesContainer = $('#field-types-'+groupHandle);

            var confirmQuestion = confirm(confirmText);

            if (confirmQuestion == true) {

                var entries = $(entriesContainer).find('.js-entry');

                entries.each(function(i, item) {
                    $(item).remove();
                });

                $(entriesContainer).append(templateNoEntries());

            }

        };

        // Change entry title on input change
        var changeEntryTitle = function(e) {

            var title = $(this).val();

            if (!title) {

                title = '#'+$(this).closest('.js-entry').attr('data-counter');

            }

            $(this).closest('.js-entry')
                .find('.js-entry-title')
                .text(title);

        };

        // Populate fields with translated or untranslated texts
        var populateTranslationFields = function(e) {

            e.preventDefault();

            var replacementType = $(this).attr('data-type');

            var inputs = $('#ccm-tab-content-texts').find('input');

            inputs.each(function(i, item) {
                if (replacementType=='translated') {
                    var newText = $(item).attr('data-translated-text');
                } else {
                    var newText = $(item).attr('data-untranslated-text');
                }
                $(item).val(newText);
            });

        };

        // Use field as title in repeatable entries
        var useFieldAsTitleInRepeatableEntries = function(e) {

            e.preventDefault();

            var clickedCheckbox = $(this);

            var formContainer = clickedCheckbox.closest('.ccm-tab-content');

            var checkboxes = formContainer.find('.js-use-field-as-title-in-repeatable-entries');
            checkboxes.each(function(i, item) {
                if ($(item).attr('name') != clickedCheckbox.attr('name') && clickedCheckbox.is(':checked')) {
                    $(item).prop('checked', false);
                }
            });

        };

        // Create thumbnail image
        var createThumbnailImage = function(e) {

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
        var createFullscreenImage = function(e) {

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

        // Delete block type folder
        var deleteBlockTypeFolder = function(e) {

            e.preventDefault();

            var ajaxCsrfToken            = $('#ajaxCsrfToken').val();
            var deleteBlockTypeFolderUrl = $('#deleteBlockTypeFolderUrl').val();
            var successMessagePart1      = $('#deleteBlockTypeFolderSuccessMessagePart1').val();
            var successMessagePart2      = $('#deleteBlockTypeFolderSuccessMessagePart2').val();
            var confirmationMessage      = $('#deleteBlockTypeFolderConfirmationMessage').val();
            var handle                   = $('#blockHandle').val();

            var confirmQuestion = confirm(confirmationMessage);

            if (confirmQuestion == true) {

                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ajaxCsrfToken : ajaxCsrfToken,
                        handle        : handle
                    },
                    url: deleteBlockTypeFolderUrl,
                    error: function(jqXHR, textStatus, errorThrown) {

                        if (errorThrown) {
                            var message = 'Oops! Something went wrong...';
                        } else {
                            var message = textStatus;
                        }

                        alert(message)

                    },
                    success: function(response) {

                        if (response['status']=='success') {

                            alert(successMessagePart1 + '\n' + successMessagePart2)

                        } else {

                            var message = response['message'];
                            this.error(this.xhr, message);

                        }

                    }
                });

            }

        };

        var initSortable = function() {

            var sortableContainers = bbContainer.find('.js-sortable');

            sortableContainers.each(function(i, item) {
                $(item).sortable({
                    handle: '.js-move-entry',
                    cursor: 'move',
                    stop: function(event,ui){
                        $('.sortable').removeClass('hover');
                    },
                    over: function(event,ui){
                        $('.ui-sortable-placeholder').parents('.sortable').addClass('hover');
                    },
                    out: function(event,ui){
                        $('.ui-sortable-placeholder').parents('.sortable').removeClass('hover');
                    },
                    change: function(event, ui) {
                        $('.ui-sortable-placeholder').css({
                            visibility: 'visible',
                            background: '#eee'
                        });
                    }
                });
            });

        };

        var initNavigationTabs = function(navContainer) {

            navContainer = $(navContainer);

            var hash = window.location.hash;
            var activeTab;

            // Find active tab (by # in url or first element)
            if (hash) {
                activeTab = hash.substring(1);
                navContainer.find('a').removeClass('navigation-tabs-active');
                navContainer.find('a[data-tab="' +activeTab+'"]').addClass('navigation-tabs-active');
            } else {
                activeTab = navContainer.children(':first').find('a').attr('data-tab');
                navContainer.children(':first').find('a').addClass('navigation-tabs-active');
            }

            // Show content of active tab
            $('#ccm-tab-content-' + activeTab).show();

            // Modify form action attribute, so hash will persist through $_POST
            navContainer.closest('form').attr('action', function(i, val) {
                return val.split('#')[0] + hash;
            });

            // On navigation click
            navContainer.find('a').click(function(e) {

                e.preventDefault();

                var activeTab = $(this).attr('data-tab');

                navContainer.find('a').removeClass('navigation-tabs-active');
                $(this).addClass('navigation-tabs-active');
                navContainer.find('a').each(function(i ,item) {
                    $('#ccm-tab-content-' + $(item).attr('data-tab')).hide();
                });
                $('#ccm-tab-content-' + activeTab).show();
                window.location.hash = '#'+activeTab;

                navContainer.closest('form').attr('action', function(i, val) {
                    return val.split('#')[0] + window.location.hash;
                });

                return false;

            });
        };

        var bindFunctions = function() {
            bbContainer.on('change', '.js-add-entry', addEntry);
            bbContainer.on('click',  '.js-remove-entry', removeEntry);
            bbContainer.on('click',  '.js-toggle-entry', toggleEntry);
            bbContainer.on('click',  '.js-expand-all', expandAllEntries);
            bbContainer.on('click',  '.js-collapse-all', collapseAllEntries);
            bbContainer.on('click',  '.js-remove-all', removeAllEntries);
            bbContainer.on('input',  '.js-entry-title-source', changeEntryTitle);
            bbContainer.on('click',  '.js-populate-translation-fields', populateTranslationFields);
            bbContainer.on('change', '.js-use-field-as-title-in-repeatable-entries', useFieldAsTitleInRepeatableEntries);
            bbContainer.on('change', '.js-image-create-thumbnail-image', createThumbnailImage);
            bbContainer.on('change', '.js-image-create-fullscreen-image', createFullscreenImage);
            $('.alert').on('click',  '.js-delete-block-type-folder', deleteBlockTypeFolder);
        };

        var init = function() {
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