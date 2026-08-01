/* global _, Choices, Sortable */

document.addEventListener('DOMContentLoaded', () => {
    const blockBuilder = (() => {
        const bbContainer = document.getElementById('bbAppBuilder');
        if (!bbContainer) return { init: () => {} };

        const globalCounter = { basic: 0, entries: 0 };

        const renderTemplate = {
            entry: _.template(bbContainer.querySelector('#templateEntry')?.innerHTML || ''),
            noEntries: _.template(bbContainer.querySelector('#templateNoEntries')?.innerHTML || ''),
        };

        const renderPartial = (handle, data) => {
            const element = bbContainer.querySelector(`[data-field-type-partial="${handle}"]`);
            return element ? _.template(element.innerHTML)(data) : '';
        };

        const toSnakeCase = (text) => {
            const numberMap = {
                0: 'zero',
                1: 'one',
                2: 'two',
                3: 'three',
                4: 'four',
                5: 'five',
                6: 'six',
                7: 'seven',
                8: 'eight',
                9: 'nine',
            };

            const tokens = text
                // Split camelCase, acronyms, and letter↔digit boundaries
                .replace(/([a-z])([A-Z])/g, '$1 $2')
                .replace(/([A-Z]+)([A-Z][a-z])/g, '$1 $2')
                .replace(/([A-Za-z])(\d)/g, '$1 $2')
                .replace(/(\d)([A-Za-z])/g, '$1 $2')
                // Split multi-digit numbers into individual digits
                .replace(/\d+/g, (match) => match.split('').join(' '))
                .split(/[^A-Za-z0-9]+/)
                .filter(Boolean);

            return tokens
                .map((token) => {
                    if (/^\d$/.test(token)) {
                        return numberMap[token];
                    }
                    return token.toLowerCase();
                })
                .join('_');
        };

        const toCamelCase = (text) => {
            const numberMap = {
                0: 'zero',
                1: 'one',
                2: 'two',
                3: 'three',
                4: 'four',
                5: 'five',
                6: 'six',
                7: 'seven',
                8: 'eight',
                9: 'nine',
            };

            const tokens = text
                // Add spaces between:
                // - lower → upper (camelCase)
                // - acronym → word (HTMLParser)
                // - letter → digit / digit → letter
                .replace(/([a-z])([A-Z])/g, '$1 $2')
                .replace(/([A-Z]+)([A-Z][a-z])/g, '$1 $2')
                .replace(/([A-Za-z])(\d)/g, '$1 $2')
                .replace(/(\d)([A-Za-z])/g, '$1 $2')
                // Split multi-digit numbers into single digits
                .replace(/\d+/g, (m) => m.split('').join(' '))
                .split(/[^A-Za-z0-9]+/)
                .filter(Boolean);

            return tokens
                .map((token, index) => {
                    if (/^\d$/.test(token)) {
                        token = numberMap[token];
                    } else {
                        token = token.toLowerCase();
                    }

                    if (index === 0) return token;

                    return token.charAt(0).toUpperCase() + token.slice(1);
                })
                .join('');
        };

        const getFieldTypeMetadata = (context, handle) => {
            const option = bbContainer.querySelector(`[data-add-entry][data-context="${context}"] option[value="${handle}"]`);
            if (!option) return null;

            return {
                handle: handle,
                name: option.textContent.trim(),
                icon: option.getAttribute('data-icon'),
                properties: JSON.parse(option.getAttribute('data-properties') || '[]'),
                defaultValues: JSON.parse(option.getAttribute('data-default-values') || '[]'),
            };
        };

        const getEntriesContainer = (context) => bbContainer.querySelector(`#bb-field-entries-${context}`);

        const addEntry = (entry, context, fieldType, counter) => {
            const container = getEntriesContainer(context);
            if (!container) return;

            const defaultProperties = fieldType.properties.reduce((acc, prop) => ({ ...acc, [prop]: '' }), {});

            const data = {
                ...defaultProperties,
                ...fieldType.defaultValues,
                ...entry,
                counter,
                context,
                fieldTypeName: fieldType.name,
                fieldTypeHandle: fieldType.handle,
                fieldTypeIcon: fieldType.icon,
            };

            data.partialContent = renderPartial(fieldType.handle, data);

            container.querySelector('[data-alert-no-entries]')?.remove();

            container.insertAdjacentHTML('beforeend', renderTemplate.entry(data));

            globalCounter[context]++;
        };

        const populateFields = (context) => {
            const container = getEntriesContainer(context);
            if (!container) return;

            const entries = JSON.parse(container.getAttribute('data-entries') || '[]');

            if (entries.length > 0) {
                entries.forEach((entry, index) => {
                    const fieldType = getFieldTypeMetadata(context, entry.fieldType);
                    if (fieldType) {
                        addEntry(entry, context, fieldType, index);
                    }
                });
            } else {
                container.insertAdjacentHTML('beforeend', renderTemplate.noEntries());
            }
        };

        const initSortable = (context) => {
            const container = getEntriesContainer(context);
            if (!container) return;

            new Sortable(container, {
                handle: '[data-move-entry]',
                animation: 150,
                ghostClass: 'bb-entry-ghost',
            });
        };

        const highlightFieldsWithErrors = () => {
            const errorData = bbContainer.getAttribute('data-fields-with-errors');
            if (!errorData) return;

            const fieldsWithErrors = JSON.parse(errorData);

            fieldsWithErrors.forEach((fieldHandle) => {
                const fieldNode = document.getElementById(fieldHandle);

                if (fieldNode) {
                    fieldNode.closest('div')?.classList.add('bb-has-error');
                    fieldNode.closest('[data-entry]')?.classList.add('bb-entry-has-error');
                }
            });
        };

        const populateTranslationFields = (e) => {
            e.preventDefault();

            const button = e.target.closest('[data-populate-translation-fields]');
            if (!button) return;

            const replacementType = button.getAttribute('data-type');
            const container = button.closest('[data-tab-content]');

            if (container) {
                container.querySelectorAll('input').forEach((item) => {
                    const newText = replacementType === 'translated' ? item.getAttribute('data-translated-text') : item.getAttribute('data-untranslated-text');

                    item.value = newText || '';
                });
            }
        };

        const toggleEntry = (e) => {
            e.preventDefault();

            const button = e.target.closest('[data-toggle-entry]');
            if (!button) return;

            const entry = button.closest('[data-entry]');
            const content = entry.querySelector('[data-entry-content]');
            const icon = button.querySelector('i');

            const isCollapsed = button.getAttribute('data-action') === 'collapse';

            if (isCollapsed) {
                content.style.display = 'none';
                icon.classList.replace('fa-minus-square', 'fa-plus-square');
                button.setAttribute('data-action', 'expand');
            } else {
                content.style.display = 'block';
                icon.classList.replace('fa-plus-square', 'fa-minus-square');
                button.setAttribute('data-action', 'collapse');
            }
        };

        const removeEntry = (e) => {
            e.preventDefault();

            const button = e.target.closest('[data-remove-entry]');
            if (!button) return;

            const confirmText = button.getAttribute('data-confirm-text');

            if (confirm(confirmText)) {
                const entry = button.closest('[data-entry]');
                const container = entry.parentElement;

                entry.remove();

                if (container.querySelectorAll('[data-entry]').length === 0) {
                    container.insertAdjacentHTML('beforeend', renderTemplate.noEntries());
                }
            }
        };

        const backToTop = (e) => {
            e.preventDefault();
            const navTabs = document.querySelector('#bb-tabs');

            if (navTabs) {
                const concreteBarHeight = document.querySelector('#ccm-toolbar')?.offsetHeight || 0;
                window.scrollTo({
                    top: navTabs.getBoundingClientRect().top + window.scrollY - concreteBarHeight - 5,
                    behavior: 'smooth',
                });
            }
        };

        const expandAllEntries = (e) => {
            e.preventDefault();
            const container = e.target.closest('[data-tab-content]');
            if (!container) return;

            container.querySelectorAll('[data-entry-content]').forEach((el) => (el.style.display = 'block'));

            container.querySelectorAll('[data-toggle-entry]').forEach((button) => {
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-plus-square');
                    icon.classList.add('fa-minus-square');
                }
                button.setAttribute('data-action', 'collapse');
            });
        };

        const collapseAllEntries = (e) => {
            e.preventDefault();
            const container = e.target.closest('[data-tab-content]');
            if (!container) return;

            container.querySelectorAll('[data-entry-content]').forEach((el) => (el.style.display = 'none'));

            container.querySelectorAll('[data-toggle-entry]').forEach((button) => {
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-minus-square');
                    icon.classList.add('fa-plus-square');
                }
                button.setAttribute('data-action', 'expand');
            });
        };

        const removeAllEntries = (e) => {
            e.preventDefault();
            const button = e.target.closest('[data-remove-all]');
            if (!button) return;

            const confirmText = button.getAttribute('data-confirm-text');
            const context = button.getAttribute('data-group-handle');
            const container = getEntriesContainer(context);

            if (confirm(confirmText)) {
                container.innerHTML = '';
                container.insertAdjacentHTML('beforeend', renderTemplate.noEntries());
            }
        };

        const toggleScroll = (e) => {
            const isChecked = e.target.checked;

            document.querySelectorAll('[data-toggle-scroll]').forEach((el) => {
                el.checked = isChecked;
            });

            if (isChecked) {
                localStorage.removeItem('scrollDisabled');
            } else {
                localStorage.setItem('scrollDisabled', '1');
            }
        };

        const changeEntryTitle = (e) => {
            const input = e.target;
            const entry = input.closest('[data-entry]');
            if (!entry) return;

            let title = input.value.trim();

            if (!title) {
                title = `#${entry.getAttribute('data-counter')}`;
            }

            const titleElement = entry.querySelector('[data-entry-title]');
            if (titleElement) {
                titleElement.textContent = title;
            }
        };

        const autoGenerateBlockHandle = (blockNameElement) => {
            const blockHandleElement = document.querySelector('#blockHandle');
            if (!blockHandleElement) return;

            if (String(blockHandleElement.value || '').trim() !== '') return;
            if (blockNameElement.dataset.handleGenerated === '1') return;

            const snakeCasedHandle = toSnakeCase(blockNameElement.value);
            if (String(snakeCasedHandle || '').trim() === '') return;

            blockHandleElement.value = snakeCasedHandle;
            blockNameElement.dataset.handleGenerated = '1';
        };

        const resetAutoGeneratedBlockHandleFlag = (blockHandleElement) => {
            if (blockHandleElement.value === '') {
                document.querySelector('#blockName')?.setAttribute('data-handle-generated', '0');
            }
        };

        const autoGenerateHandle = (input) => {
            const entry = input.closest('[data-entry]');
            if (!entry) return;

            const handleElement = entry.querySelector('[data-entry-handle]');
            if (!handleElement) return;

            const handleWasAutoGenerated = entry.dataset.handleGenerated === '1';
            if (entry.dataset.handleGenerated === '0') return;
            if (String(handleElement.value || '').trim() !== '' && !handleWasAutoGenerated) return;

            const camelCasedHandle = toCamelCase(input.value);
            handleElement.value = camelCasedHandle;
            entry.dataset.handleGenerated = '1';
        };

        const stopAutoGeneratingHandle = (input) => {
            const entry = input.closest('[data-entry]');
            if (entry) entry.dataset.handleGenerated = '0';
        };

        const restartHandleAutoGenerationWhenEmpty = (input) => {
            if (String(input.value || '').trim() !== '') return;

            input.closest('[data-entry]')?.removeAttribute('data-handle-generated');
        };

        const handleTitleInRepeatableEntries = (e) => {
            const clickedCheckbox = e.target;
            const formContainer = clickedCheckbox.closest('[data-tab-content]');
            if (!formContainer) return;

            if (clickedCheckbox.checked) {
                formContainer.querySelectorAll('[data-use-field-as-title-in-repeatable-entries]').forEach((item) => {
                    if (item.getAttribute('name') !== clickedCheckbox.getAttribute('name')) {
                        item.checked = false;
                    }
                });
            }
        };

        const toggleThumbnailOptions = (e) => {
            const clickedCheckbox = e.target;
            const entryContent = clickedCheckbox.closest('[data-entry-content]');
            const optionsWrapper = entryContent?.querySelector('[data-image-create-thumbnail-image-wrapper]');

            if (optionsWrapper) {
                optionsWrapper.classList.toggle('d-none', !clickedCheckbox.checked);
            }
        };

        const toggleFullscreenImageOptions = (e) => {
            const clickedCheckbox = e.target;
            const entryContent = clickedCheckbox.closest('[data-entry-content]');
            const optionsWrapper = entryContent?.querySelector('[data-image-create-fullscreen-image-wrapper]');

            if (optionsWrapper) {
                optionsWrapper.classList.toggle('d-none', !clickedCheckbox.checked);
            }
        };

        const toggleSelectListGenerationOptions = (e) => {
            const selectField = e.target;
            const entryContent = selectField.closest('[data-entry-content]');
            if (!entryContent) return;

            const listWrapper = entryContent.querySelector('[data-select-list-generation-method="basic_list"]');
            const customCodeWrapper = entryContent.querySelector('[data-select-list-generation-method="custom_code"]');

            if (listWrapper) listWrapper.classList.add('d-none');
            if (customCodeWrapper) customCodeWrapper.classList.add('d-none');

            if (selectField.value === 'custom_code') {
                customCodeWrapper?.classList.remove('d-none');
            } else {
                listWrapper?.classList.remove('d-none');
            }
        };

        const initTabs = () => {
            const navContainer = bbContainer.querySelector('#bb-tabs');
            if (!navContainer) return;

            const updateFormActionHash = () => {
                const form = navContainer.closest('form');
                if (form && window.location.hash) {
                    const baseUrl = form.action.split('#')[0];
                    form.action = baseUrl + window.location.hash;
                }
            };

            const switchTab = (tabHandle) => {
                const link = navContainer.querySelector(`[data-bb-tab="${tabHandle}"]`);
                const content = document.getElementById(`ccm-tab-content-${tabHandle}`);

                if (!link || !content) return;

                navContainer.querySelectorAll('[data-bb-tab]').forEach((el) => el.removeAttribute('data-bb-tab-active'));
                link.setAttribute('data-bb-tab-active', '');

                document.querySelectorAll('.ccm-tab-content').forEach((el) => (el.style.display = 'none'));
                content.style.display = 'block';

                history.replaceState(null, null, `#${tabHandle}`);
                updateFormActionHash();
            };

            const hash = window.location.hash.substring(1);
            const firstTab = navContainer.querySelector('[data-bb-tab]')?.getAttribute('data-bb-tab');
            switchTab(hash || firstTab);
            updateFormActionHash();

            navContainer.addEventListener('click', (e) => {
                const link = e.target.closest('[data-bb-tab]');
                if (link) {
                    e.preventDefault();
                    switchTab(link.getAttribute('data-bb-tab'));

                    const form = navContainer.closest('form');
                    if (form) {
                        const baseUrl = form.action.split('#')[0];
                        form.action = baseUrl + window.location.hash;
                    }
                }
            });
        };

        const initAddEntryDropdown = () => {
            bbContainer.querySelectorAll('[data-add-entry]').forEach((element) => {
                const choices = new Choices(element, {
                    shouldSort: false,
                    searchEnabled: true,
                    itemSelectText: '',
                    callbackOnCreateTemplates: (template) => ({
                        item: ({ classNames }, data) => {
                            const option = element.querySelector(`option[value="${data.value}"]`);
                            const icon = option?.getAttribute('data-icon');
                            return template(`
                                <div class="${classNames.item} ${data.highlighted ? classNames.highlightedState : classNames.itemSelectable}" data-item data-id="${data.id}" data-value="${data.value}" ${data.active ? 'aria-selected="true"' : ''}>
                                    ${icon ? `<i class="${icon} fa-fw" style="margin-right: 10px; width: 1.25em; text-align: center; display: inline-block;"></i>` : ''} ${data.label}
                                </div>
                            `);
                        },
                        choice: ({ classNames }, data) => {
                            const option = element.querySelector(`option[value="${data.value}"]`);
                            const icon = option?.getAttribute('data-icon');
                            return template(`
                                <div class="${classNames.item} ${classNames.itemChoice} ${data.disabled ? classNames.itemDisabled : classNames.itemSelectable}" data-choice data-id="${data.id}" data-value="${data.value}">
                                    ${icon ? `<i class="${icon} fa-fw" style="margin-right: 10px; width: 1.25em; text-align: center; display: inline-block;"></i>` : ''} ${data.label}
                                </div>
                                `);
                        },
                    }),
                });

                element.addEventListener('change', (e) => {
                    const handle = e.currentTarget.value;
                    const context = e.currentTarget.getAttribute('data-context');

                    if (handle) {
                        const fieldType = getFieldTypeMetadata(context, handle);
                        if (fieldType) {
                            addEntry({}, context, fieldType, globalCounter[context]);
                        }

                        // Mark field that was recently added
                        getEntriesContainer(context)
                            .querySelectorAll('[data-recently-added]')
                            .forEach((el) => {
                                el.removeAttribute('data-recently-added');
                            });
                        const newField = getEntriesContainer(context).lastElementChild;
                        if (newField) {
                            newField.setAttribute('data-recently-added', 'true');
                        }

                        // Smooth scroll
                        if (!localStorage.getItem('scrollDisabled')) {
                            const targetEntry = getEntriesContainer(context).querySelector(`[data-entry][data-counter="${globalCounter[context] - 1}"]`);
                            if (targetEntry) {
                                const concreteBarHeight = document.querySelector('#ccm-toolbar')?.offsetHeight || 0;
                                const bbActionsBarHeight =
                                    targetEntry.closest('[data-tab-content]').querySelector('[data-field-type-actions]')?.offsetHeight || 0;
                                window.scrollTo({
                                    top: targetEntry.getBoundingClientRect().top + window.scrollY - concreteBarHeight - bbActionsBarHeight,
                                    behavior: 'smooth',
                                });
                            }
                        }

                        // Reset field
                        choices.setChoiceByValue('');
                    }
                });
            });
        };

        const initBlockIconPicker = () => {
            const element = document.getElementById('blockIcon');
            if (!element) return;

            new Choices(element, {
                shouldSort: false,
                searchEnabled: true,
                itemSelectText: '',
                callbackOnCreateTemplates: (template) => ({
                    item: ({ classNames }, data) =>
                        template(`
                            <div class="${classNames.item} ${data.highlighted ? classNames.highlightedState : classNames.itemSelectable}" data-item data-id="${data.id}" data-value="${data.value}" ${data.active ? 'aria-selected="true"' : ''} ${data.disabled ? 'aria-disabled="true"' : ''}>
                                <img src="${data.value}" style="width: 24px; height: 24px; margin-right: 10px; vertical-align: middle;" alt="${data.label}"> ${data.label}
                            </div>
                        `),
                    choice: ({ classNames }, data) =>
                        template(`
                            <div class="${classNames.item} ${classNames.itemChoice} ${data.disabled ? classNames.itemDisabled : classNames.itemSelectable}" data-choice ${data.disabled ? 'aria-disabled="true"' : 'aria-haspopup="true"'} data-id="${data.id}" data-value="${data.value}" data-choice-selectable>
                                <img src="${data.value}" style="width: 24px; height: 24px; margin-right: 10px; vertical-align: middle;" alt="${data.label}"> ${data.label}
                            </div>
                        `),
                }),
            });

            const previewImg = document.querySelector('#blockIconPreviewImage');
            const updatePreview = (url) => {
                if (previewImg && url) {
                    previewImg.src = url;
                }
            };

            element.addEventListener('change', (e) => updatePreview(e.detail.value));
            updatePreview(element.value);
        };

        const bindFunctions = () => {
            bbContainer.addEventListener('click', (e) => {
                const target = e.target;

                if (target.closest('[data-populate-translation-fields]')) populateTranslationFields(e);
                if (target.closest('[data-toggle-entry]')) toggleEntry(e);
                if (target.closest('[data-remove-entry]')) removeEntry(e);
                if (target.closest('[data-back-to-top]')) backToTop(e);
                if (target.closest('[data-expand-all]')) expandAllEntries(e);
                if (target.closest('[data-collapse-all]')) collapseAllEntries(e);
                if (target.closest('[data-remove-all]')) removeAllEntries(e);
            });

            bbContainer.addEventListener('input', (e) => {
                if (e.target.closest('[data-entry-title-source]')) {
                    changeEntryTitle(e);
                    autoGenerateHandle(e.target);
                }
            });

            bbContainer.addEventListener('focusout', (e) => {
                if (e.target.matches('#blockName')) return autoGenerateBlockHandle(e.target);
                if (e.target.matches('#blockHandle')) return resetAutoGeneratedBlockHandleFlag(e.target);
                if (e.target.matches('[data-entry-title-source]')) return stopAutoGeneratingHandle(e.target);
                if (e.target.matches('[data-entry-handle]')) return restartHandleAutoGenerationWhenEmpty(e.target);
            });

            bbContainer.addEventListener('change', (e) => {
                const target = e.target;

                if (target.closest('[data-use-field-as-title-in-repeatable-entries]')) handleTitleInRepeatableEntries(e);
                if (target.closest('[data-image-create-thumbnail-image]')) toggleThumbnailOptions(e);
                if (target.closest('[data-image-create-fullscreen-image]')) toggleFullscreenImageOptions(e);
                if (target.closest('[data-change-select-list-generation-method]')) toggleSelectListGenerationOptions(e);
            });

        };

        return {
            init: () => {
                ['basic', 'entries'].forEach(populateFields);
                ['basic', 'entries'].forEach(initSortable);
                initTabs();
                initAddEntryDropdown();
                initBlockIconPicker();
                highlightFieldsWithErrors();
                bindFunctions();

                const isScrollDisabled = localStorage.getItem('scrollDisabled') === '1';
                bbContainer.querySelectorAll('[data-toggle-scroll]').forEach((el) => {
                    el.checked = !isScrollDisabled;
                    el.addEventListener('change', toggleScroll);
                });
            },
        };
    })();

    blockBuilder.init();
});
