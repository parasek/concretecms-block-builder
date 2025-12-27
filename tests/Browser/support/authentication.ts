import { expect, type Page } from '@playwright/test';

export async function loginAsDisposableAdmin(page: Page): Promise<void> {
    const username = process.env.BLOCK_BUILDER_BROWSER_ADMIN_USERNAME;
    const password = process.env.BLOCK_BUILDER_BROWSER_ADMIN_PASSWORD;
    if (!username || !password) {
        throw new Error('Disposable browser-test administrator credentials are missing.');
    }

    await page.goto('/index.php/login');
    await expect(page.locator('#uName')).toBeVisible();
    await page.locator('#uName').fill(username);
    await page.locator('#uPassword').fill(password);
    await Promise.all([
        page.waitForURL((url) => !url.pathname.endsWith('/login')),
        page.getByRole('button', { name: 'Sign In' }).click(),
    ]);

    await page.goto('/index.php/dashboard/blocks/block_builder');
    await expect(page.locator('#bbAppBuilder')).toBeVisible();
}
