import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/browser',
    outputDir: './storage/framework/testing/playwright',
    reporter: 'line',
    use: {
        baseURL: process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8000',
        locale: 'de-DE',
        timezoneId: 'Europe/Berlin',
        trace: 'retain-on-failure',
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
