import { expect, test } from '@playwright/test';

test('login page remains usable', async ({ page }) => {
    await page.goto('/login', { waitUntil: 'domcontentloaded' });

    await expect(page.getByText('Login', { exact: true }).first()).toBeVisible();
    await expect(page.getByLabel('E-Mail Address')).toBeVisible();
    await expect(page.getByLabel('Password')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Login' })).toBeVisible();
});

test('@visual login page baseline', async ({ page }) => {
    await page.route('**/js/main_build.js', route => route.abort());
    await page.route('https://fonts.googleapis.com/**', route => route.fulfill({
        body: '',
        contentType: 'text/css',
        status: 200,
    }));
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await page.evaluate(() => document.activeElement?.blur());
    await expect(page).toHaveScreenshot('login-page.png', {
        animations: 'disabled',
        caret: 'hide',
        timeout: 15_000,
    });
});
