import {expect, test} from '@playwright/test';
import {installRalewayFixture} from './ralewayFixture.mjs';
import {viteScriptTag, viteStylesheetTags} from './viteAssets.js';

test.beforeEach(async ({page}) => installRalewayFixture(page));

test('user edits profile and opens password dialog on desktop and mobile', async ({page}, testInfo) => {
    const saved = [];
    const profile = {id: 1, name: 'Synthetic User', email: 'synthetic@example.invalid', created_at: '2026-09-14T10:00:00.000000Z', password_min_length: 12};

    await page.route('**/vue/profil', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">${viteStylesheetTags}</head><body><div id="app"></div><script>window.Laravel = {csrfToken: 'synthetic-csrf-token'}; window.materialpool = {route: '/profil', store: {materials: []}};</script>${viteScriptTag}</body></html>`,
    }));
    await page.route('**/api/v1/general/options*', route => route.fulfill({contentType: 'application/json', body: JSON.stringify({
        systemname: 'MaterialPool Default', server: {max_upload: 10485760},
        user: {...profile, is_admin: false, permissions: [], frontend_user_settings: {}},
    })}));
    await page.route(/\/api\/v2\/profile(?:\/|\?|$)/, route => {
        const request = route.request();
        const pathname = new URL(request.url()).pathname;
        if (request.method() === 'PUT' && pathname.endsWith('/password')) {
            saved.push({pathname, payload: request.postDataJSON()});
            return route.fulfill({contentType: 'application/json', body: JSON.stringify({password_changed: true})});
        }
        if (request.method() === 'PATCH') {
            const payload = request.postDataJSON();
            saved.push({pathname, payload});
            if (pathname.endsWith('/name')) profile.name = payload.name.trim();
            if (pathname.endsWith('/email')) profile.email = payload.email.toLowerCase();
        }
        return route.fulfill({contentType: 'application/json', body: JSON.stringify(profile)});
    });

    await page.goto('/vue/profil');
    await expect(page.getByRole('heading', {name: 'Profil'})).toBeVisible();
    await page.getByRole('textbox', {name: 'Name'}).fill('Neue Person');
    await page.locator('section').filter({has: page.getByRole('heading', {name: 'Name'})}).getByRole('button', {name: 'Speichern'}).click();
    await expect(page.getByText('Name gespeichert.')).toBeVisible();

    await page.getByRole('textbox', {name: 'E-Mail-Adresse'}).fill('neu@example.invalid');
    await page.getByLabel('Aktuelles Passwort').first().fill('altes-passwort');
    await page.locator('section').filter({has: page.getByRole('heading', {name: 'E-Mail-Adresse'})}).getByRole('button', {name: 'Speichern'}).click();
    await expect(page.getByText('E-Mail-Adresse gespeichert.')).toBeVisible();
    expect(saved).toEqual([
        {pathname: '/api/v2/profile/name', payload: {name: 'Neue Person'}},
        {pathname: '/api/v2/profile/email', payload: {email: 'neu@example.invalid', current_password: 'altes-passwort'}},
    ]);

    await page.getByRole('button', {name: 'Passwort ändern'}).click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(page.getByLabel('Neues Passwort wiederholen')).toBeVisible();
    await page.screenshot({path: testInfo.outputPath('profile-password-modal.png')});
    await page.getByLabel('Neues Passwort', {exact: true}).fill('neues-langes-passwort');
    await page.getByRole('button', {name: 'Abbrechen'}).click();
    await page.getByRole('button', {name: 'Passwort ändern'}).click();
    await expect(page.getByLabel('Neues Passwort', {exact: true})).toHaveValue('');
    await page.getByRole('button', {name: 'Abbrechen'}).click();

    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({path: testInfo.outputPath('profile.png'), fullPage: true});
    if (testInfo.project.name === 'mobile-webkit') await page.locator('.navbar-toggler').click();
    await page.getByRole('link', {name: 'Neue Person'}).click();
    await expect(page.getByRole('menuitem', {name: 'Profil'})).toBeVisible();
    expect(await page.locator('body').evaluate(body => body.scrollWidth)).toBe(await page.locator('body').evaluate(body => body.clientWidth));

    await page.route('**/login', route => route.fulfill({contentType: 'text/html', body: '<!doctype html><html><body>Anmeldung</body></html>'}));
    await page.getByRole('button', {name: 'Passwort ändern'}).click();
    const dialog = page.getByRole('dialog');
    await dialog.getByLabel('Aktuelles Passwort').fill('altes-passwort');
    await dialog.getByLabel('Neues Passwort', {exact: true}).fill('neues-langes-passwort');
    await dialog.getByLabel('Neues Passwort wiederholen').fill('neues-langes-passwort');
    await dialog.getByRole('button', {name: 'Speichern'}).click();
    await expect(page).toHaveURL(/\/login$/);
    expect(saved.at(-1)).toEqual({pathname: '/api/v2/profile/password', payload: {
        current_password: 'altes-passwort', password: 'neues-langes-passwort', password_confirmation: 'neues-langes-passwort',
    }});
});
