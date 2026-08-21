import AxeBuilder from '@axe-core/playwright';
import { expect, test, type Locator, type Page } from '@playwright/test';
import { loginAsDisposableAdmin } from './support/authentication';
import { assertDisposableBrowserEnvironment } from './support/environment';

const generatedFixtureHandle = 'block_builder_browser_fixture';
const exactGeneratedFixtureHandle = /^\s*block_builder_browser_fixture\s*$/;

async function addFieldThroughChoices(
    builder: Locator,
    context: 'basic' | 'entries',
    fieldTypeHandle: string,
): Promise<void> {
    const originalSelect = builder.locator(`select[data-add-entry][data-context="${context}"]`);
    const choicesWidget = originalSelect.locator(
        'xpath=ancestor::div[contains(concat(" ", normalize-space(@class), " "), " choices ")][1]',
    );

    await choicesWidget.locator('.choices__inner').click();
    const fieldTypeChoice = choicesWidget.locator(`[data-choice][data-value="${fieldTypeHandle}"]`);
    await expect(fieldTypeChoice).toBeVisible();
    await fieldTypeChoice.click();
}

async function submitBuildForm(page: Page, builder: Locator): Promise<void> {
    const buildButton = builder.locator('button[name="buildBlock"]');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        buildButton.evaluate((button: HTMLButtonElement) => {
            const form = button.form;
            if (!form) {
                throw new Error('The build button is not associated with a form.');
            }

            form.noValidate = true;
            form.requestSubmit(button);
        }),
    ]);
}

async function removeGeneratedFixtureThroughDashboard(page: Page): Promise<void> {
    for (let action = 0; action < 2; action += 1) {
        await page.goto('/index.php/dashboard/blocks/block_builder/configs');
        const fixture = page.locator('.bb-block-type').filter({
            has: page.locator('.bb-block-type-handle').filter({ hasText: exactGeneratedFixtureHandle }),
        });
        const fixtureCount = await fixture.count();
        if (fixtureCount === 0) {
            return;
        }
        if (fixtureCount !== 1) {
            throw new Error(`Refusing browser cleanup: expected one exact ${generatedFixtureHandle} fixture, found ${fixtureCount}.`);
        }
        const fixtureHandle = (await fixture.locator('.bb-block-type-handle').innerText()).trim();
        if (fixtureHandle !== generatedFixtureHandle) {
            throw new Error(`Refusing browser cleanup for unexpected handle: ${fixtureHandle}`);
        }

        const uninstallForm = fixture.locator('.bb-block-type-action-uninstall form');
        if (await uninstallForm.count() === 1) {
            await expect(uninstallForm).toHaveAttribute('method', /post/i);
            await expect(uninstallForm.locator('input[type="hidden"][name="ccm_token"]')).toHaveCount(1);
            page.once('dialog', (dialog) => dialog.accept());
            await Promise.all([
                page.waitForNavigation(),
                uninstallForm.locator('button[type="submit"]').click(),
            ]);
            continue;
        }

        const deleteForm = fixture.locator(
            `.bb-block-type-action-delete-folder form[data-block-type-handle="${generatedFixtureHandle}"]`,
        );
        if (await deleteForm.count() !== 1) {
            throw new Error(`Refusing browser cleanup: ${generatedFixtureHandle} has no exact guarded lifecycle form.`);
        }
        await expect(deleteForm).toHaveAttribute('method', /post/i);
        await expect(deleteForm.locator('input[type="hidden"][name="ccm_token"]')).toHaveCount(1);
        page.once('dialog', (dialog) => dialog.accept());
        await Promise.all([
            page.waitForNavigation(),
            deleteForm.locator('button[type="submit"]').click(),
        ]);
        await expect(page.locator('.bb-block-type-handle').filter({ hasText: exactGeneratedFixtureHandle })).toHaveCount(0);

        return;
    }

    throw new Error(`Unable to remove exact browser fixture ${generatedFixtureHandle} through dashboard lifecycle controls.`);
}

test.beforeEach(async ({ request }) => {
    await assertDisposableBrowserEnvironment(request);
});

test('dashboard requires authentication', async ({ page }) => {
    await page.goto('/index.php/dashboard/blocks/block_builder');

    await expect(page).toHaveURL(/\/login(?:[/?#]|$)/);
    await expect(page.locator('#uName')).toBeVisible();
    await expect(page.locator('#bbAppBuilder')).toHaveCount(0);
});

test.describe('authenticated Block Builder dashboard', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsDisposableAdmin(page);
    });

    test('loads predefined configs without critical accessibility violations', async ({ page }) => {
        await page.goto('/index.php/dashboard/blocks/block_builder/configs');

        await expect(page.locator('.bb-app-configs')).toBeVisible();
        await expect(page.locator('.bb-block-type-handle').filter({ hasText: /^\s*all_fields\s*$/ })).toBeVisible();
        await expect(page.locator('.bb-block-type-handle').filter({
            hasText: /^\s*single_multiple_choice_field\s*$/,
        })).toBeVisible();

        const accessibilityResult = await new AxeBuilder({ page })
            .include('.bb-app-configs')
            .withTags(['wcag2a', 'wcag2aa'])
            .analyze();
        const criticalViolations = accessibilityResult.violations.filter(
            (violation) => violation.impact === 'critical',
        );
        expect(criticalViolations).toEqual([]);
    });

    test('hydrates tabs and manages a field without submitting generated files', async ({ page }) => {
        await page.goto('/index.php/dashboard/blocks/block_builder');
        const builder = page.locator('#bbAppBuilder');

        await expect(builder.locator('[data-bb-tab-active]')).toHaveAttribute('data-bb-tab', 'block-settings');
        await builder.locator('#blockName').fill('Browser Fixture 42');
        await expect(builder.locator('#blockHandle')).toHaveValue('browser_fixture_four_two');
        await expect(builder.locator('[data-block-identity] [data-handle-autogeneration-overlay]')).toBeVisible();
        await builder.locator('#blockName').blur();
        await expect(builder.locator('[data-block-identity] [data-handle-autogeneration-overlay]')).toBeHidden();
        await builder.locator('#blockHandle').fill('manual_browser_handle');
        await builder.locator('#blockName').fill('A different browser name');
        await expect(builder.locator('#blockHandle')).toHaveValue('manual_browser_handle');
        await builder.locator('#blockName').blur();
        await builder.locator('#blockHandle').fill('');
        await builder.locator('#blockHandle').blur();
        await builder.locator('#blockName').fill('Regenerated Browser 7');
        await expect(builder.locator('#blockHandle')).toHaveValue('regenerated_browser_seven');
        await builder.locator('#blockName').blur();

        await builder.locator('[data-bb-tab="tab-basic-information"]').click();
        await expect(page).toHaveURL(/#tab-basic-information$/);
        await expect(builder.locator('#ccm-tab-content-tab-basic-information')).toBeVisible();

        await addFieldThroughChoices(builder, 'basic', 'text_field');
        const addedField = builder.locator('#bb-field-entries-basic [data-entry]').last();
        await expect(addedField).toBeVisible();
        const requiredIndicator = addedField.locator('[data-entry-required-indicator]');
        await expect(requiredIndicator).toBeHidden();
        await addedField.locator('[data-entry-title-source]').fill('Product Name 2');
        await addedField.locator('[data-entry-title-source]').blur();
        await expect(addedField.locator('[data-entry-title]')).toHaveText('Product Name 2');
        await expect(addedField.locator('[data-entry-handle]')).toHaveValue('productNameTwo');
        await addedField.locator('[data-entry-required-source]').check();
        await expect(requiredIndicator).toBeVisible();
        await expect(addedField.locator('.bb-entry-header-title')).toContainText('Product Name 2 *');
        await addedField.locator('[data-entry-handle]').fill('manualProductName');
        await addedField.locator('[data-entry-title-source]').fill('Changed product name');
        await expect(addedField.locator('[data-entry-handle]')).toHaveValue('manualProductName');

        page.once('dialog', (dialog) => dialog.accept());
        await addedField.locator('[data-remove-entry]').click();
        await expect(addedField).toHaveCount(0);
    });

    test('repeatable title source remains exclusive and entries can be removed', async ({ page }) => {
        const builder = page.locator('#bbAppBuilder');
        await builder.locator('[data-bb-tab="tab-repeatable-entries"]').click();
        await addFieldThroughChoices(builder, 'entries', 'text_field');
        await addFieldThroughChoices(builder, 'entries', 'text_field');
        const entries = builder.locator('#bb-field-entries-entries [data-entry]');
        await expect(entries).toHaveCount(2);
        await entries.nth(0).locator('[data-entry-title-source]').fill('First title');
        await entries.nth(1).locator('[data-entry-title-source]').fill('Second title');

        const firstTitleSource = entries.nth(0).locator('[data-use-field-as-title-in-repeatable-entries]');
        const secondTitleSource = entries.nth(1).locator('[data-use-field-as-title-in-repeatable-entries]');
        const firstTitleIndicator = entries.nth(0).locator('[data-entry-title-source-indicator]');
        const secondTitleIndicator = entries.nth(1).locator('[data-entry-title-source-indicator]');
        await expect(firstTitleIndicator).toBeHidden();
        await expect(secondTitleIndicator).toBeHidden();
        await firstTitleSource.check();
        await expect(firstTitleSource).toBeChecked();
        await expect(firstTitleIndicator).toBeVisible();
        await expect(firstTitleIndicator).toHaveText('Entry title');
        await secondTitleSource.check();
        await expect(secondTitleSource).toBeChecked();
        await expect(firstTitleSource).not.toBeChecked();
        await expect(firstTitleIndicator).toBeHidden();
        await expect(secondTitleIndicator).toBeVisible();

        page.once('dialog', (dialog) => dialog.accept());
        await entries.nth(0).locator('[data-remove-entry]').click();
        await expect(entries).toHaveCount(1);
    });

    test('failed POST retains values, focuses the error tab, and accepts a valid PNG upload', async ({ page }) => {
        const builder = page.locator('#bbAppBuilder');
        const blockName = builder.locator('#blockName');
        const blockHandle = builder.locator('#blockHandle');
        await blockName.fill('Retained browser name');
        await blockName.blur();
        await expect(blockHandle).toHaveValue('retained_browser_name');
        await blockHandle.fill('retained_browser_handle');
        await blockHandle.blur();
        await expect(blockHandle).toHaveValue('retained_browser_handle');
        await builder.locator('#blockDescription').fill('Retained browser description');

        const iconBytes = await page.evaluate(async () => {
            const canvas = document.createElement('canvas');
            canvas.width = 97;
            canvas.height = 97;
            const context = canvas.getContext('2d');
            if (!context) throw new Error('Canvas is unavailable.');
            context.fillStyle = '#2457a6';
            context.fillRect(0, 0, 97, 97);
            const blob = await new Promise<Blob>((resolve, reject) => {
                canvas.toBlob((result) => result ? resolve(result) : reject(new Error('PNG creation failed.')), 'image/png');
            });

            return Array.from(new Uint8Array(await blob.arrayBuffer()));
        });
        await builder.locator('#customBlockIcon').setInputFiles({
            name: 'browser-fixture.png',
            mimeType: 'image/png',
            buffer: Buffer.from(iconBytes),
        });

        await builder.locator('[data-bb-tab="labels"]').click();
        await builder.locator('#addAtTheTopLabel').fill('');
        await builder.locator('#addAtTheBottomLabel').fill('');
        await submitBuildForm(page, builder);

        await expect(page).toHaveURL(/#labels$/);
        await expect(page.locator('[data-bb-tab="labels"]')).toHaveClass(/bb-tab-has-error/);
        await expect(page.locator('.bb-alert-list')).toContainText('At least one label');
        await expect(page.locator('#blockName')).toHaveValue('Retained browser name');
        await expect(page.locator('#blockHandle')).toHaveValue('retained_browser_handle');
        await expect(page.locator('#blockDescription')).toHaveValue('Retained browser description');
        await expect(page.locator('.bb-alert-list')).not.toContainText(/97px|PNG image/i);
    });

    test('generated config escapes stored names and rejects unguarded lifecycle requests', async ({ page, context }) => {
        test.setTimeout(90_000);

        const storedName = '<img src=x onerror="window.__blockBuilderStoredXss=true"> Browser fixture';
        await page.addInitScript(() => {
            (window as Window & { __blockBuilderStoredXss?: boolean }).__blockBuilderStoredXss = false;
        });
        await removeGeneratedFixtureThroughDashboard(page);

        try {
            await page.goto('/index.php/dashboard/blocks/block_builder/predefined_config/single_multiple_choice_field');
            const builder = page.locator('#bbAppBuilder');
            await builder.locator('#blockName').fill(storedName);
            await builder.locator('#blockHandle').fill(generatedFixtureHandle);
            await builder.locator('[data-bb-tab="build-options"]').click();
            await expect(builder.locator('#ccm-tab-content-build-options')).toBeVisible();
            await builder.locator('#installBlock').selectOption('0');
            await submitBuildForm(page, builder);
            await expect(page).toHaveURL(/\/dashboard\/blocks\/block_builder\/config\/block_builder_browser_fixture/);

            await page.goto('/index.php/dashboard/blocks/block_builder/configs');
            const fixture = page.locator('.bb-block-type').filter({
                has: page.locator('.bb-block-type-handle').filter({ hasText: exactGeneratedFixtureHandle }),
            });
            await expect(fixture).toHaveCount(1);
            await expect(fixture.locator('.bb-block-type-name')).toHaveText(storedName);
            await expect(fixture.locator('img[src="x"]')).toHaveCount(0);
            expect(await page.evaluate(() => (
                window as Window & { __blockBuilderStoredXss?: boolean }
            ).__blockBuilderStoredXss)).toBe(false);

            const lifecycleForms = fixture.locator('.bb-block-type-action-install form, .bb-block-type-action-delete-folder form');
            const formCount = await lifecycleForms.count();
            expect(formCount).toBeGreaterThan(0);
            for (let index = 0; index < formCount; index += 1) {
                const form = lifecycleForms.nth(index);
                await expect(form).toHaveAttribute('method', /post/i);
                await expect(form.locator('input[type="hidden"][name="ccm_token"]')).toHaveCount(1);

                const action = await form.getAttribute('action');
                if (!action) {
                    throw new Error('A guarded lifecycle form is missing its action URL.');
                }
                const actionUrl = new URL(action, page.url()).toString();
                const rejectedRequestFactories = [
                    () => context.request.get(actionUrl, { failOnStatusCode: false }),
                    () => context.request.post(actionUrl, { form: {}, failOnStatusCode: false }),
                    () => context.request.post(actionUrl, {
                        form: { ccm_token: 'invalid-browser-token' },
                        failOnStatusCode: false,
                    }),
                ];
                for (const createRejectedRequest of rejectedRequestFactories) {
                    await createRejectedRequest();
                    await page.goto('/index.php/dashboard/blocks/block_builder/configs');
                    await expect(fixture).toHaveCount(1);
                    await expect(fixture.locator('.bb-block-type-action-install form')).toHaveCount(1);
                    await expect(fixture.locator('.bb-block-type-action-delete-folder form')).toHaveCount(1);
                    await expect(fixture.locator('.bb-block-type-action-uninstall form')).toHaveCount(0);
                }
            }
        } finally {
            if (!page.isClosed() && test.info().status !== 'timedOut') {
                await removeGeneratedFixtureThroughDashboard(page);
            }
        }
    });
});
