import { defineConfig, devices } from '@playwright/test';

function requireEnvironmentValue(name: string): string {
    const value = process.env[name]?.trim();
    if (!value) {
        throw new Error(`The required browser-test environment variable ${name} is missing.`);
    }

    return value;
}

if (process.env.BLOCK_BUILDER_BROWSER_DISPOSABLE !== '1') {
    throw new Error(
        'Browser tests are disabled. Set BLOCK_BUILDER_BROWSER_DISPOSABLE=1 only for a disposable CMS installation.',
    );
}

const baseUrl = new URL(requireEnvironmentValue('BLOCK_BUILDER_BROWSER_BASE_URL'));
if (!['127.0.0.1', 'localhost', '[::1]'].includes(baseUrl.hostname)) {
    throw new Error('Browser tests may target only a loopback-hosted disposable CMS installation.');
}
if (!['http:', 'https:'].includes(baseUrl.protocol)) {
    throw new Error('The browser-test base URL must use HTTP or HTTPS.');
}

requireEnvironmentValue('BLOCK_BUILDER_BROWSER_ADMIN_USERNAME');
requireEnvironmentValue('BLOCK_BUILDER_BROWSER_ADMIN_PASSWORD');
requireEnvironmentValue('BLOCK_BUILDER_BROWSER_SITE_ID');

export default defineConfig({
    testDir: '.',
    testMatch: '**/*.spec.ts',
    fullyParallel: false,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: process.env.CI
        ? [['line'], ['html', { outputFolder: 'playwright-report', open: 'never' }]]
        : [['list'], ['html', { outputFolder: 'playwright-report', open: 'never' }]],
    outputDir: 'test-results',
    use: {
        baseURL: baseUrl.toString(),
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
        {
            name: 'firefox',
            use: { ...devices['Desktop Firefox'] },
        },
    ],
});
