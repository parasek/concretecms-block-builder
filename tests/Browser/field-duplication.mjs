// Standalone browser regression test: no CMS, credentials, or generated block writes.
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import { chromium } from '@playwright/test';

const require = createRequire(import.meta.url);
const browser = await chromium.launch();
try {
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.route('http://block-builder.test/', (route) =>
        route.fulfill({
            contentType: 'text/html',
            body: execFileSync('php', [fileURLToPath(new URL('./support/render-field-duplication.php', import.meta.url))], { encoding: 'utf8' }),
        })
    );
    await page.goto('http://block-builder.test/');
    await page.addStyleTag({ path: fileURLToPath(new URL('../../assets/css/styles.css', import.meta.url)) });
    await page.addStyleTag({
        content: '.position-relative { position: relative; } i { display: inline-block; width: 1em; height: 1em; } .d-none { display: none; }',
    });
    await page.addScriptTag({ path: require.resolve('lodash/lodash.js') });
    await page.evaluate(() => {
        window.Choices = class {};
        window.Sortable = class {};
        localStorage.setItem('scrollDisabled', '1');
    });
    await page.addScriptTag({ path: fileURLToPath(new URL('../../assets/js/block-builder.js', import.meta.url)) });
    await page.evaluate(() => document.dispatchEvent(new Event('DOMContentLoaded')));

    for (const context of ['basic', 'entries']) {
        const container = page.locator(`#bb-field-entries-${context}`);
        const editor = container
            .locator('[data-entry]')
            .filter({ has: page.locator('[name$="[fieldType]"][value="wysiwyg_editor"]') })
            .first();
        const preset = editor.locator('[data-load-editor-preset]');
        assert.equal(await preset.getAttribute('name'), null);
        assert.equal(await preset.locator('option[value="default"]').textContent(), 'Default editor - No custom configuration, all tags allowed');
        const allowedTags = editor.locator('[name$="[allowedTags]"]');
        const customConfig = editor.locator('[name$="[customConfig]"]');
        await allowedTags.fill('<p>');
        await customConfig.fill('{"toolbar":[]}');
        await preset.selectOption('');
        assert.equal(await allowedTags.inputValue(), '<p>');
        assert.equal(await customConfig.inputValue(), '{"toolbar":[]}');
        let warningCount = 0;
        const rejectPreset = async (dialog) => {
            warningCount++;
            assert.match(dialog.message(), /overwrite Allowed Tags and Custom editor configuration/);
            await dialog.dismiss();
        };
        page.on('dialog', rejectPreset);
        await preset.selectOption('simple_editor');
        assert.equal(warningCount, 1);
        assert.equal(await allowedTags.inputValue(), '<p>');
        assert.equal(await customConfig.inputValue(), '{"toolbar":[]}');
        assert.equal(await preset.inputValue(), '');
        // Either nonempty value on its own also requires confirmation.
        await customConfig.fill('');
        await preset.selectOption('default');
        assert.equal(warningCount, 2);
        await allowedTags.fill('');
        await customConfig.fill('{"toolbar":[]}');
        await preset.selectOption('default');
        assert.equal(warningCount, 3);
        page.off('dialog', rejectPreset);
        const acceptPreset = async (dialog) => {
            warningCount++;
            await dialog.accept();
        };
        page.on('dialog', acceptPreset);
        await preset.selectOption('simple_editor');
        assert.equal(await allowedTags.inputValue(), '<span><b><strong><i><em><u><sub><sup><br>');
        assert.deepEqual(
            JSON.parse(await customConfig.inputValue()).toolbar.map((group) => group.name),
            ['document', 'basicstyles', 'links']
        );
        await preset.selectOption('editor_without_links');
        assert.equal(await allowedTags.inputValue(), '<div><p><blockquote><span><b><strong><i><em><u><sub><sup><br><h1><h2><h3><h4><h5><h6><ul><ol><li>');
        assert.deepEqual(
            JSON.parse(await customConfig.inputValue()).toolbar.map((group) => group.name),
            ['document', 'basicstyles', 'paragraph', 'styles', 'links']
        );
        await preset.selectOption('default');
        assert.equal(await allowedTags.inputValue(), '');
        assert.equal(await customConfig.inputValue(), '');
        assert.equal(warningCount, 6);
        await preset.selectOption('simple_editor');
        assert.equal(warningCount, 6, 'Empty fields must not prompt');
        assert.equal(await preset.inputValue(), '');
        page.off('dialog', acceptPreset);
        // Loading a preset does not make subsequent manual edits transient.
        await allowedTags.fill('<strong>');
        await customConfig.fill('{"toolbar":["Bold"]}');
        const originalCount = await container.locator('[data-entry]').count();
        const source = container.locator('[data-entry]').first();
        await source.locator('[name$="[label]"]').fill('Edited <title>');
        await source.locator('[name$="[helpText]"]').fill('Changed help');
        await source.locator('[name$="[required]"]').check();
        if (context === 'entries') await source.locator('[name$="[titleSource]"]').check();
        await source.locator('[data-toggle-entry]').click();
        await source.locator('[data-duplicate-entry="after"] i').click();
        const copy = container.locator('[data-entry]').nth(1);
        assert.equal(await copy.locator('[name$="[label]"]').inputValue(), 'Edited <title>');
        assert.equal(await copy.locator('[name$="[helpText]"]').inputValue(), 'Changed help');
        assert.equal(await copy.locator('[name$="[required]"]').isChecked(), true);
        assert.equal(await copy.locator('[data-entry-content]').isVisible(), true);
        if (context === 'entries') assert.equal(await copy.locator('[name$="[titleSource]"]').isChecked(), true);
        assert.equal(await copy.locator('[data-entry-handle]').inputValue(), await source.locator('[data-entry-handle]').inputValue());
        await copy.locator('[data-duplicate-entry="at-end"] svg').click();
        assert.equal(await container.locator('[data-entry]').count(), originalCount + 2);
        assert.equal(await container.locator('[data-entry]').last().locator('[name$="[label]"]').inputValue(), 'Edited <title>');

        // Every field type must retain current controls, including selects and SVG definitions.
        const originals = await container.locator('[data-entry]').all();
        for (const original of originals) {
            const before = await original.locator('input, textarea, select').evaluateAll((controls) =>
                controls.map((control) => ({
                    property: control.name.replace(/^\w+\[\d+\]/, ''),
                    value: control.type === 'checkbox' ? control.checked : control.value,
                }))
            );
            await original.locator('[data-duplicate-entry="at-end"]').click();
            const after = await container
                .locator('[data-entry]')
                .last()
                .locator('input, textarea, select')
                .evaluateAll((controls) =>
                    controls.map((control) => ({
                        property: control.name.replace(/^\w+\[\d+\]/, ''),
                        value: control.type === 'checkbox' ? control.checked : control.value,
                    }))
                );
            assert.deepEqual(after, before);
        }
    }
    const identifiers = await page.locator('[id]').evaluateAll((elements) => elements.map((element) => element.id));
    assert.equal(new Set(identifiers).size, identifiers.length, 'DOM IDs must remain unique');
    assert.deepEqual(errors, []);
    console.log('Field duplication passed for both collections and all predefined field types.');
} finally {
    await browser.close();
}
