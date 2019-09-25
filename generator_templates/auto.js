$(function() {

    Concrete.event.bind('open.block.[[[BLOCK_HANDLE_DASHED]]]', function(e, data) {

        var uniqueID         = data.uniqueID;
        var formContainer    = $('#form-container-'+uniqueID);
        var entriesContainer = formContainer.find('#entries-'+uniqueID);

        function activateEditors(parentContainer) {

            var editors = parentContainer.find('.js-editor-content');
            editors.each(function(i, item) {
                activateEditor(item);
            });

        }

        function activatePageSelectors(parentContainer) {

            var pageSelectors = parentContainer.find('.js-page-selector');
            pageSelectors.each(function(i, item) {
                var inputName = $(item).attr('data-input-name');
                var cID = parseInt($(item).attr('data-collection-id'));
                $(item).concretePageSelector({'inputName': inputName, 'cID': cID});
            });

        }

        function activateFileSelectors(parentContainer) {

            var fileSelectors = parentContainer.find('.js-file-selector');
            fileSelectors.each(function(i, item) {
                var inputName = $(item).attr('data-input-name');
                var fID = parseInt($(item).attr('data-file-id'));
                $(item).concreteFileSelector({'inputName': inputName, 'filters': [], 'fID': fID});
            });

        }

        function activateHtmlEditors(parentContainer) {

            var htmlEditors = parentContainer.find('.js-html-editor');
            htmlEditors.each(function(i, item) {
                var htmlEditorEntry = ace.edit($(item).attr('id'));
                htmlEditorEntry.setTheme('ace/theme/eclipse');
                htmlEditorEntry.getSession().setMode('ace/mode/html');
                htmlEditorEntry.getSession().on('change', function() {
                    $(item).next().val(htmlEditorEntry.getValue());
                });
            });

        }

        function countEntries(parentContainer) {

            var numberOfEntries = parentContainer.children().length;

            var firstElement = parentContainer.children().first();

            if (firstElement.hasClass('js-alert')) {
                numberOfEntries -= 1;
            }

            return numberOfEntries;

        }

        // Populate form with existing entries
        var entries = JSON.parse(entriesContainer.attr('data-entries'));

        var position = entries!=null ? entries.length : 0;
        var template = _.template(formContainer.find('.js-entry-template').html());
        var templateNoEntries = _.template(formContainer.find('.js-template-no-entries').html());

        if (entries && entries.length) {
            $.each(entries, function (index, item) {
                entriesContainer.append(template(item));
            });
        } else {
            entriesContainer.append(templateNoEntries());
        }

        // Activate c5 tools/editors
        activateEditors(formContainer);

        activatePageSelectors(formContainer);

        activateFileSelectors(formContainer);

        activateHtmlEditors(formContainer);

        // Add entry
        formContainer.on('click', '.js-add-entry', function(e) {

            position++;

            if (countEntries(entriesContainer)==0) {
                entriesContainer.html('');
            }

            // Append/prepend entry with default values
            var entryColumnNames  = JSON.parse(entriesContainer.attr('data-column-names'));
            var templateData = [];
            templateData['position'] = position;
            $.each(entryColumnNames, function(key, value) {
                templateData[value] = '';
            });

            var action = $(this).attr('data-action');
            var newEntry = false;

            if (action=='prepend') {
                entriesContainer.prepend(template(templateData));
                newEntry = entriesContainer.children(':first');
            } else {
                entriesContainer.append(template(templateData));
                newEntry = entriesContainer.children(':last');
            }

            // Highlight newly added entry
            newEntry.effect('highlight', {}, 1500);

            // Activate c5 tools/editors
            activateEditors(newEntry);

            activatePageSelectors(newEntry);

            activateFileSelectors(newEntry);

            activateHtmlEditors(newEntry);

            // Smooth scroll
            $(this).closest('.ui-dialog-content').animate({
                scrollTop: formContainer.find('.js-entry[data-position="'+position+'"]').position().top + $(this).closest('.ui-dialog-content').scrollTop()
            });

        });

        // Delete entry
        entriesContainer.on('click', '.js-remove-entry', function(e2) {

            e2.preventDefault();

            var dataConfirmText = $(this).attr('data-confirm-text');

            var confirmQuestion = confirm(dataConfirmText);

            if (confirmQuestion == true) {

                var editorIDs = $(this).closest('.js-entry').find('.js-editor-content');

                editorIDs.each(function(i, item) {

                    var editorID = $(item).attr('id');
                    if (typeof CKEDITOR === 'object' && typeof CKEDITOR.instances[editorID] != 'undefined') {
                        CKEDITOR.instances[editorID].destroy();
                    }

                });

                $(this).closest('.js-entry').remove();

                if (countEntries(entriesContainer)==0) {

                    entriesContainer.append(templateNoEntries());

                }
            }

        });

        // Change header title on input change
        entriesContainer.on('input', '.js-entry-title-source', function() {

            var title = $(this).val();

            if (!title) {
                title = '#'+$(this).closest('.js-entry').attr('data-position');
            }

            $(this).closest('.js-entry')
                .find('.js-entry-title')
                .text(title);

        });

        // Expand all entries
        formContainer.on('click', '.js-expand-all', function(e2) {

            e2.preventDefault();

            var formContainer = $(this).closest('.js-tab-content');

            formContainer.find('.js-entry-content').show();

            var toggleButtons = formContainer.find('.js-toggle-entry');
            toggleButtons.find('i').removeClass('fa-plus-square-o');
            toggleButtons.find('i').addClass('fa-minus-square-o');
            toggleButtons.attr('data-action', 'collapse');

        });

        // Collapse all entries
        formContainer.on('click', '.js-collapse-all', function(e2) {

            e2.preventDefault();

            var formContainer = $(this).closest('.js-tab-content');

            formContainer.find('.js-entry-content').hide();

            var toggleButtons = formContainer.find('.js-toggle-entry');
            toggleButtons.find('i').removeClass('fa-minus-square-o');
            toggleButtons.find('i').addClass('fa-plus-square-o');
            toggleButtons.attr('data-action', 'expand');

        });

        // Toggle entry
        entriesContainer.on('click', '.js-toggle-entry', function(e2) {

            e2.preventDefault();

            var position = $(this).closest('.js-entry').attr('data-position');

            entriesContainer.find('.js-entry[data-position="'+position+'"] .js-entry-content').toggle();

            if ($(this).attr('data-action')=='collapse') {

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

        // Change external link protocol
        entriesContainer.on('keyup change', '.js-external-link-url', function(e) {

            var url = $(this).val();

            if (url.indexOf('https://') == 0) {
                $(this).val(url.substring(8));
                $(this).parent().closest('.row').find('.js-external-link-protocol').val(url.substring(0, 8));
            } else if (url.indexOf('http://') == 0) {
                $(this).val(url.substring(7));
                $(this).parent().closest('.row').find('.js-external-link-protocol').val(url.substring(0, 7));
            }

        });

    });

});