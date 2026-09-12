import { expect, test } from '@playwright/test';

test.beforeEach(async ({page}) => {
    await page.route('https://fonts.googleapis.com/**', route => route.fulfill({
        body: '',
        contentType: 'text/css',
        status: 200,
    }));
});

async function stabilizeDebugToolbar(page) {
    const requestDuration = page.locator(
        '.phpdebugbar-indicator:has(.phpdebugbar-icon-clock) .phpdebugbar-text',
    );

    await expect(requestDuration).toHaveCount(1);
    await requestDuration.evaluate(element => {
        element.textContent = '0ms';
    });
}

test('login page remains usable', async ({ page }) => {
    await page.goto('/login', { waitUntil: 'domcontentloaded' });

    await expect(page.getByText('Login', { exact: true }).first()).toBeVisible();
    await expect(page.getByLabel('E-Mail Address')).toBeVisible();
    await expect(page.getByLabel('Password')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Login' })).toBeVisible();
});

test('@visual login page baseline', async ({ page }) => {
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('load');
    await page.evaluate(() => document.activeElement?.blur());
    await stabilizeDebugToolbar(page);
    await expect(page).toHaveScreenshot('login-page.png', {
        animations: 'disabled',
        caret: 'hide',
        timeout: 15_000,
    });
});
