import { defineConfig, devices } from '@playwright/test';

const externalBaseUrl = process.env.PLAYWRIGHT_BASE_URL;
const baseURL = externalBaseUrl || 'http://127.0.0.1:8000';

export default defineConfig({
    testDir: './tests/browser',
    outputDir: './storage/framework/testing/playwright',
    reporter: 'line',
    use: {
        baseURL,
        locale: 'de-DE',
        timezoneId: 'Europe/Berlin',
        trace: 'retain-on-failure',
    },
    // A supplied URL belongs to an already managed environment. Local runs
    // start their own disposable server, making the npm scripts self-contained.
    webServer: externalBaseUrl ? undefined : {
        command: 'APP_ENV=dev APP_DEBUG=1 php -S 127.0.0.1:8000 -t public',
        url: `${baseURL}/login`,
        reuseExistingServer: !process.env.CI,
        stdout: 'ignore',
        stderr: 'ignore',
        timeout: 30_000,
    },
    projects: [
        {
            name: 'desktop-webkit',
            use: { ...devices['Desktop Safari'], viewport: { width: 1440, height: 900 } },
        },
        {
            name: 'mobile-webkit',
            use: { ...devices['iPhone 13'] },
        },
    ],
});
