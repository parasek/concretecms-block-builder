import { expect, type APIRequestContext } from '@playwright/test';

export async function assertDisposableBrowserEnvironment(request: APIRequestContext): Promise<void> {
    const expectedSiteIdentifier = process.env.BLOCK_BUILDER_BROWSER_SITE_ID;
    if (!expectedSiteIdentifier) {
        throw new Error('The expected disposable browser site identifier is missing.');
    }

    const response = await request.get('/__block_builder_test_environment', { failOnStatusCode: false });
    expect(response.status()).toBe(200);
    expect(response.headers()['x-block-builder-test-site']).toBe(expectedSiteIdentifier);
    expect(await response.json()).toEqual({
        purpose: 'block_builder_browser_test',
        siteIdentifier: expectedSiteIdentifier,
    });
}
