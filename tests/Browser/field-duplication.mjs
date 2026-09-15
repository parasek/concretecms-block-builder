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
    await page.route('http://block-builder.test/', (route) => route.fulfill({
        contentType: 'text/html',
        body: execFileSync('php', [fileURLToPath(new URL('./support/render-field-duplication.php', import.meta.url))], { encoding: 'utf8' }),
    }));
    await page.goto('http://block-builder.test/');
    await page.addStyleTag({ path: fileURLToPath(new URL('../../assets/css/styles.css', import.meta.url)) });
    await page.addStyleTag({ content: '.position-relative { position: relative; } i { display: inline-block; width: 1em; height: 1em; } .d-none { display: none; }' });
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
            const before = await original.locator('input, textarea, select').evaluateAll((controls) => controls.map((control) => ({
                property: control.name.replace(/^\w+\[\d+\]/, ''),
                value: control.type === 'checkbox' ? control.checked : control.value,
            })));
            await original.locator('[data-duplicate-entry="at-end"]').click();
            const after = await container.locator('[data-entry]').last().locator('input, textarea, select').evaluateAll((controls) => controls.map((control) => ({
                property: control.name.replace(/^\w+\[\d+\]/, ''),
                value: control.type === 'checkbox' ? control.checked : control.value,
            })));
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
