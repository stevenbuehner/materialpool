import {expect, test} from '@playwright/test';

test('Vue application mounts with synthetic bootstrap data', async ({page}, testInfo) => {
    const pageErrors = [];
    const compatWarnings = [];
    const unexpectedWarnings = [];
    page.on('pageerror', error => pageErrors.push(error.stack || error.message));
    page.on('console', message => {
        if (message.type() === 'warning') {
            const warning = message.text();
            const isKnownCompatWarning = warning.startsWith('[Vue warn]: (deprecation ')
                || warning.startsWith('[BootstrapVue warn]: Multiple instances of Vue detected!');

            if (isKnownCompatWarning) {
                compatWarnings.push(warning);
            } else {
                unexpectedWarnings.push(warning);
            }
        }
    });

    await page.route('**/vue/', async route => {
        await route.fulfill({
            contentType: 'text/html',
            body: `<!doctype html>
                <html lang="de">
                    <head><meta charset="utf-8"><title>Materialpool Compat Test</title></head>
                    <body>
                        <div id="app"></div>
                        <script>
                            window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                            window.materialpool = {store: {materials: []}};
                        </script>
                        <script src="/js/main_build.js"></script>
                    </body>
                </html>`,
        });
    });
    await page.route('**/api/v1/general/options?*', route => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
            systemname: 'MaterialPool Default',
            server: {max_upload: 10485760},
            user: {
                id: 1,
                name: 'Synthetic User',
                email: 'synthetic@example.invalid',
                is_admin: false,
                frontend_user_settings: {},
            },
        }),
    }));

    await page.goto('/vue/');

    await expect(page.locator('.main-area')).toBeVisible();
    await expect(page.getByRole('link', {name: 'Suchen'})).toBeVisible();
    await expect(page.locator('svg.sb-navbar-icon')).toHaveCount(2);

    await testInfo.attach('vue-compat-warnings', {
        body: JSON.stringify(compatWarnings, null, 2),
        contentType: 'application/json',
    });
    expect(pageErrors).toEqual([]);
    expect(unexpectedWarnings).toEqual([]);
});

test('Vue 3 datepicker keeps the German input and calendar interaction', async ({page}) => {
    const pageErrors = [];
    page.on('pageerror', error => pageErrors.push(error.stack || error.message));

    await page.route('**/vue/**', async route => {
        await route.fulfill({
            contentType: 'text/html',
            body: `<!doctype html>
                <html lang="de">
                    <head><meta charset="utf-8"><title>Materialpool Datepicker Test</title></head>
                    <body>
                        <div id="app"></div>
                        <script>
                            window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                            window.materialpool = {
                                route: '/material/1',
                                store: {materials: []},
                            };
                        </script>
                        <script src="/js/main_build.js"></script>
                    </body>
                </html>`,
        });
    });
    await page.route('**/api/**', async route => {
        const pathname = route.request().url()
            .replace(/^https?:\/\/[^/]+/, '')
            .split('?')[0];

        if (pathname === '/api/v1/general/options') {
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    systemname: 'MaterialPool Default',
                    server: {max_upload: 10485760},
                    user: {
                        id: 1,
                        name: 'Synthetic User',
                        email: 'synthetic@example.invalid',
                        is_admin: false,
                        frontend_user_settings: {},
                    },
                }),
            });
            return;
        }

        if (pathname === '/api/v1/materials/1') {
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    id: 1,
                    title: 'Testmaterial',
                    description: '',
                    rating: 10,
                    flag: null,
                    author: null,
                    creator: null,
                    from_bot: false,
                    created_at: '2026-09-01 12:00:00',
                    updated_at: '2026-09-01 12:00:00',
                    resources: [],
                    keywords: [],
                    bibleverses: [],
                    foreign_ids: [],
                }),
            });
            return;
        }

        if (pathname === '/api/v1/resources/find') {
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    data: [],
                    current_page: 1,
                    last_page: 1,
                    per_page: 20,
                    total: 0,
                }),
            });
            return;
        }

        await route.fulfill({contentType: 'application/json', body: '[]'});
    });
    await page.route('**/pool/search/**', route => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
            data: [],
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
        }),
    }));

    await page.goto('/vue/');

    const dateInput = page.getByPlaceholder('Datum');
    await expect(dateInput).toBeVisible();
    await expect(dateInput).toHaveValue('01.09.2026');
    await dateInput.click();
    await expect(page.locator('.vdp-datepicker__calendar').first()).toBeVisible();
    expect(pageErrors).toEqual([]);
});
