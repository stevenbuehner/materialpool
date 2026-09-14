import {expect, test} from '@playwright/test';
import {installRalewayFixture} from './ralewayFixture.mjs';
import {viteScriptTag, viteStylesheetTags} from './viteAssets.js';

test.beforeEach(async ({page}) => installRalewayFixture(page));

test('Global admin manages users and groups on desktop and mobile', async ({page}, testInfo) => {
    const createdUsers = [];
    const defaultGroup = {
        id: 10,
        name: 'Standardnutzer',
        permissions: [
            'materials.create',
            'materials.update-own',
            'materials.update-metadata-own',
            'materials.delete-own',
            'resources.create',
            'resources.update-own',
            'resources.delete-own',
        ],
        users_count: 1,
    };
    const users = [{
        id: 1,
        name: 'Global Admin',
        email: 'admin@example.invalid',
        status: 'active',
        is_admin: true,
        groups: [],
        created_at: '2026-09-14T10:00:00.000000Z',
        updated_at: '2026-09-14T10:00:00.000000Z',
    }];

    await page.route('**/vue/admin/users', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html>
            <html lang="de">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Materialpool Benutzerverwaltung</title>
                    ${viteStylesheetTags}
                </head>
                <body>
                    <div id="app"></div>
                    <script>
                        window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                        window.materialpool = {route: '/admin/users', store: {materials: []}};
                    </script>
                    ${viteScriptTag}
                </body>
            </html>`,
    }));

    await page.route('**/api/**', async route => {
        const request = route.request();
        const pathname = new URL(request.url()).pathname;

        if (pathname === '/api/v1/general/options') {
            await route.fulfill({contentType: 'application/json', body: JSON.stringify({
                systemname: 'MaterialPool Default',
                server: {max_upload: 10485760},
                user: {
                    id: 1,
                    name: 'Global Admin',
                    email: 'admin@example.invalid',
                    is_admin: true,
                    permissions: ['materials.create', 'keywords.manage', 'bundles.manage', 'system.shutdown'],
                    frontend_user_settings: {},
                },
            })});
            return;
        }

        if (pathname === '/api/v2/admin/groups') {
            await route.fulfill({contentType: 'application/json', body: JSON.stringify({data: [defaultGroup]})});
            return;
        }

        if (pathname === '/api/v2/admin/permissions') {
            await route.fulfill({contentType: 'application/json', body: JSON.stringify({data: [
                {code: 'materials.create', area: 'materials'},
                {code: 'materials.update-own', area: 'materials'},
                {code: 'resources.create', area: 'resources'},
            ]})});
            return;
        }

        if (pathname === '/api/v2/admin/users' && request.method() === 'GET') {
            await route.fulfill({contentType: 'application/json', body: JSON.stringify({
                data: users,
                current_page: 1,
                last_page: 1,
                total: users.length,
            })});
            return;
        }

        if (pathname === '/api/v2/admin/users' && request.method() === 'POST') {
            const payload = request.postDataJSON();
            createdUsers.push(payload);
            users.push({
                id: 2,
                ...payload,
                status: 'invited',
                groups: payload.group_ids.includes(defaultGroup.id) ? [defaultGroup] : [],
                created_at: '2026-09-14T11:00:00.000000Z',
                updated_at: '2026-09-14T11:00:00.000000Z',
            });
            await route.fulfill({status: 201, contentType: 'application/json', body: JSON.stringify({
                data: users.at(-1),
                invitation_sent: true,
            })});
            return;
        }

        await route.fulfill({contentType: 'application/json', body: '{}'});
    });

    await page.goto('/vue/admin/users');

    await expect(page.getByRole('heading', {name: 'Benutzerverwaltung'})).toBeVisible();
    await expect(page.getByRole('cell', {name: 'Global Admin'})).toBeVisible();
    await expect(page.getByText('Standardnutzer', {exact: true}).last()).toBeVisible();
    await expect(page.getByText('materials.create', {exact: true})).toBeVisible();

    const invitationForm = page.locator('form').filter({has: page.getByRole('heading', {name: 'Benutzer einladen'})});
    await invitationForm.getByLabel('Name').fill('Neue Person');
    await invitationForm.getByLabel('E-Mail').fill('new@example.invalid');
    await invitationForm.getByRole('button', {name: 'Speichern'}).click();

    await expect.poll(() => createdUsers.length).toBe(1);
    expect(createdUsers[0]).toMatchObject({
        name: 'Neue Person',
        email: 'new@example.invalid',
        group_ids: [10],
        is_admin: false,
    });
    await expect(page.getByRole('cell', {name: 'Neue Person'})).toBeVisible();

    await page.getByLabel('Suche').focus();
    await page.keyboard.press('Tab');
    await expect(page.getByLabel('Status').first()).toBeFocused();
    expect(await page.locator('body').evaluate(body => body.scrollWidth)).toBe(
        await page.locator('body').evaluate(body => body.clientWidth),
    );
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({fullPage: true, path: testInfo.outputPath('admin-users.png')});
});
