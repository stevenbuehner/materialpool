import {expect, test} from '@playwright/test';
import {Buffer} from 'node:buffer';

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
                    <head>
                        <meta charset="utf-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                        <title>Materialpool Compat Test</title>
                        <link rel="stylesheet" href="/css/main.css">
                    </head>
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
    await page.route('**/pool/search/get?*', route => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
            data: [],
            current_page: 1,
            from: null,
            last_page: 1,
            next_page_url: null,
            per_page: 30,
            prev_page_url: null,
            to: null,
            total: 0,
        }),
    }));

    await page.goto('/vue/');

    await expect(page.locator('.main-area')).toBeVisible();
    await expect(page.getByRole('link', {name: 'Suchen'})).toBeVisible();
    await expect(page.locator('svg.sb-navbar-icon')).toHaveCount(2);
    await expect(page).toHaveScreenshot('compat-app-home.png', {
        animations: 'disabled',
        caret: 'hide',
    });

    const speedSearch = page.getByPlaceholder('Schnellsuche');
    const navbarToggle = page.locator('.navbar-toggler');
    const navbarCollapse = page.locator('#nav_collapse');
    if (testInfo.project.name.startsWith('mobile')) {
        await expect(navbarToggle).toBeVisible();
        await expect(navbarToggle).toHaveAttribute('aria-expanded', 'false');
        await expect(navbarCollapse).not.toHaveClass(/\bshow\b/);
        await navbarToggle.click();
        await expect(navbarToggle).toHaveAttribute('aria-expanded', 'true');
        await expect(navbarCollapse).toHaveClass(/\bshow\b/);
    } else {
        await expect(navbarToggle).toBeHidden();
    }

    const accountDropdown = page.locator('.b-nav-dropdown').last();
    const accountToggle = accountDropdown.locator('.dropdown-toggle');
    const accountMenu = accountDropdown.locator('.dropdown-menu');
    await accountToggle.press('ArrowDown');
    await expect(accountToggle).toHaveAttribute('aria-expanded', 'true');
    await expect(accountMenu).toBeVisible();
    await expect(accountMenu.locator('.dropdown-item').first()).toBeFocused();
    await page.keyboard.press('Escape');
    await expect(accountMenu).toBeHidden();
    await expect(accountToggle).toBeFocused();

    await expect(speedSearch).toHaveClass(/form-control-sm/);
    await speedSearch.fill('Gamma');
    await page.locator('form').filter({has: speedSearch}).getByRole('button', {name: 'Suchen'}).click();
    await expect(page).toHaveURL(/\/vue\/search\/1\*Gamma$/);

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
                    <head>
                        <meta charset="utf-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                        <title>Materialpool Datepicker Test</title>
                        <link rel="stylesheet" href="/css/main.css">
                    </head>
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
    await expect(page).toHaveScreenshot('material-detail.png', {
        animations: 'disabled',
        caret: 'hide',
    });
    await dateInput.click();
    await expect(page.locator('.vdp-datepicker__calendar').first()).toBeVisible();

    const tabs = page.locator('.sideTab [role="tab"]');
    await expect(tabs).toHaveCount(3);
    await expect(tabs.nth(0)).toHaveAttribute('aria-selected', 'true');
    await tabs.nth(1).click();
    await expect(page).toHaveURL(/tabIndex=1/);
    await expect(tabs.nth(1)).toHaveAttribute('aria-selected', 'true');
    await tabs.nth(1).press('ArrowRight');
    await expect(page).toHaveURL(/tabIndex=2/);
    await expect(tabs.nth(2)).toBeFocused();
    await expect(tabs.nth(2)).toHaveAttribute('aria-selected', 'true');

    await tabs.nth(1).click();
    const assignButton = page.getByRole('button', {name: 'Resource zuordnen'});
    await assignButton.click();

    const modal = page.locator('.modal.show');
    await expect(modal).toBeVisible();
    await expect(modal.locator('.modal-dialog')).toHaveClass(/\bmodal-lg\b/);
    await expect(modal.getByRole('heading', {name: 'Wähle eine Resource'})).toBeVisible();
    await expect(page.locator('.modal-backdrop.show')).toBeVisible();
    await expect(page.locator('body')).toHaveClass(/\bmodal-open\b/);
    await expect(modal.locator('.modal-header .close')).toBeFocused();
    await expect(page).toHaveScreenshot('material-resource-modal.png', {
        animations: 'disabled',
        caret: 'hide',
    });

    await page.keyboard.press('Escape');
    await expect(modal).toBeHidden();
    await expect(page.locator('.modal-backdrop.show')).toHaveCount(0);
    await expect(page.locator('body')).not.toHaveClass(/\bmodal-open\b/);
    await expect(assignButton).toBeFocused();
    expect(pageErrors).toEqual([]);
});

test('Vue 3 select keeps asynchronous search and object selection', async ({page}) => {
    const pageErrors = [];
    page.on('pageerror', error => pageErrors.push(error.stack || error.message));

    await page.route('**/vue/**', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html>
            <html lang="de">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Materialpool Select Test</title>
                    <link rel="stylesheet" href="/css/main.css">
                </head>
                <body>
                    <div id="app"></div>
                    <script>
                        window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                        window.materialpool = {
                            route: '/search',
                            store: {materials: []},
                        };
                    </script>
                    <script src="/js/main_build.js"></script>
                </body>
            </html>`,
    }));
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
    await page.route('**/pool/search/get?*', route => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
            data: [],
            current_page: 1,
            from: null,
            last_page: 1,
            next_page_url: null,
            per_page: 30,
            prev_page_url: null,
            to: null,
            total: 0,
        }),
    }));
    await page.route('**/pool/search/guess?*', route => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
            data: [{
                text: 'Alpha',
                icon: '/img/icons/ayce.svg',
                item: {type: '*', text: 'Alpha'},
            }],
        }),
    }));

    await page.goto('/vue/search');

    const select = page.locator('.searchInputSelect');
    const searchInput = page.locator('.searchInputSelect input[role="combobox"]');
    await expect(select).toHaveCSS('display', 'block');
    await expect(select).toHaveCSS('border-top-width', '0px');
    await expect(select.locator('.vs__dropdown-toggle')).toHaveCSS('border-top-width', '1px');
    await expect(searchInput).toBeVisible();
    await searchInput.fill('Alp');
    await expect(page.locator('.vs__dropdown-option').filter({hasText: 'Alpha'})).toBeVisible();
    await searchInput.press('Tab');
    await expect(page.locator('.searchInputSelect .sb-search-input-tag')).toContainText('Alpha');
    await expect(page).toHaveURL(/\/vue\/search\/1\*Alpha$/);
    expect(pageErrors).toEqual([]);
});

test('Resource detail cards and multi-page pagination keep their application contracts', async ({page}) => {
    const pageErrors = [];
    const consoleErrors = [];
    const unexpectedWarnings = [];
    page.on('pageerror', error => pageErrors.push(error.stack || error.message));
    page.on('console', message => {
        if (message.type() === 'error') consoleErrors.push(message.text());
        if (message.type() === 'warning' && !message.text().startsWith('[Vue warn]: (deprecation ')) {
            unexpectedWarnings.push(message.text());
        }
    });

    let initialRoute = '/resource/42';
    await page.route('**/vue/**', route => {
        return route.fulfill({
            contentType: 'text/html',
            body: `<!doctype html>
                <html lang="de">
                    <head>
                        <meta charset="utf-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                        <title>Materialpool Resource and Pagination Test</title>
                        <link rel="stylesheet" href="/css/main.css">
                    </head>
                    <body>
                        <div id="app"></div>
                        <script>
                            window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                            window.materialpool = {route: ${JSON.stringify(initialRoute)}, store: {materials: []}};
                        </script>
                        <script src="/js/main_build.js"></script>
                    </body>
                </html>`,
        });
    });
    await page.route('**/api/**', async route => {
        const requestUrl = new URL(route.request().url());
        const pathname = requestUrl.pathname;

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

        if (pathname === '/api/v1/resources/42') {
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    id: 42,
                    type: 'text',
                    original_filename: 'synthetic-resource.txt',
                    content: '# Synthetic resource\n\nStable content.',
                    notes: 'Stable note',
                    remote_path: '',
                    is_public: false,
                    materials: [],
                    creator: null,
                    created_at: '2026-09-01 12:00:00',
                    updated_at: '2026-09-02 13:00:00',
                    content_hash: 'synthetic-content-hash',
                    filesize: 1024,
                    page_count: null,
                }),
            });
            return;
        }

        if (pathname === '/api/v1/materials') {
            const pageNumber = Number.parseInt(requestUrl.searchParams.get('page') || '1', 10);
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    data: [{
                        id: 70 + pageNumber,
                        title: `Synthetic material page ${pageNumber}`,
                        description: 'Stable material description',
                        author: null,
                        from_bot: false,
                        icon_of_bundle: null,
                        resources: [{id: 42, type: 'text'}],
                        keywords: [],
                        bibleverses: [],
                    }],
                    current_page: pageNumber,
                    last_page: 12,
                    per_page: 1,
                    total: 12,
                }),
            });
            return;
        }

        await route.fulfill({contentType: 'application/json', body: '[]'});
    });

    await page.goto('/vue/');
    await page.waitForTimeout(100);
    expect(pageErrors).toEqual([]);
    expect(consoleErrors).toEqual([]);
    expect(unexpectedWarnings).toEqual([]);
    const resourceCard = page.locator('.resource > .card');
    await expect(resourceCard).toBeVisible();
    await expect(resourceCard.getByRole('heading', {name: 'synthetic-resource.txt'})).toBeVisible();
    await expect(resourceCard.getByRole('heading', {name: 'Synthetic resource'})).toBeVisible();
    await resourceCard.getByRole('tab', {name: 'MetaInfo'}).click();
    await expect(resourceCard).toContainText('synthetic-content-hash');
    await expect(page).toHaveScreenshot('resource-detail.png', {
        animations: 'disabled',
        caret: 'hide',
    });

    initialRoute = '/material?page=6';
    await page.goto('/vue/');
    await page.waitForTimeout(100);
    expect(pageErrors).toEqual([]);
    expect(consoleErrors).toEqual([]);
    expect(unexpectedWarnings).toEqual([]);
    const materialCard = page.locator('.materialpool-card-columns > .card.resource');
    await expect(materialCard).toBeVisible();
    await expect(materialCard).toContainText('Synthetic material page 6');
    const pagination = page.locator('.pagination');
    await expect(pagination.locator('.page-item.active')).toHaveText('6');
    await expect(pagination.locator('[role="separator"]')).toHaveCount(1);
    await pagination.getByRole('link', {name: '7', exact: true}).click();
    await expect(page).toHaveURL(/\/vue\/material\?page=7$/);
    await expect(materialCard).toContainText('Synthetic material page 7');
    await expect(pagination.locator('.page-item.active')).toHaveText('7');
    await expect(page).toHaveScreenshot('material-pagination.png', {
        animations: 'disabled',
        caret: 'hide',
    });

    expect(pageErrors).toEqual([]);
    expect(consoleErrors).toEqual([]);
    expect(unexpectedWarnings).toEqual([]);
});

test('Vue 3 uploader keeps multipart success and error handling', async ({page}) => {
    const pageErrors = [];
    const uploadRequests = [];
    let rejectUpload = false;
    page.on('pageerror', error => pageErrors.push(error.stack || error.message));

    await page.route('**/vue/**', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html>
            <html lang="de">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Materialpool Upload Test</title>
                    <link rel="stylesheet" href="/css/main.css">
                </head>
                <body>
                    <div id="app"></div>
                    <script>
                        window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                        window.materialpool = {
                            route: '/resource/create',
                            store: {materials: []},
                        };
                    </script>
                    <script src="/js/main_build.js"></script>
                </body>
            </html>`,
    }));
    await page.route('**/api/**', async route => {
        const request = route.request();
        const pathname = request.url()
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

        if (pathname === '/api/v1/resources' && request.method() === 'POST') {
            uploadRequests.push({
                body: request.postData() || '',
                csrf: request.headers()['x-csrf-token'],
            });

            if (rejectUpload) {
                await route.fulfill({
                    status: 422,
                    contentType: 'application/json',
                    body: JSON.stringify({error: ' pencils are not supported'}),
                });
                return;
            }

            await route.fulfill({
                status: 201,
                contentType: 'application/json',
                body: JSON.stringify({
                    id: 90 + uploadRequests.length,
                    type: 'file',
                    title: 'synthetic-upload.txt',
                }),
            });
            return;
        }

        await route.fulfill({contentType: 'application/json', body: '[]'});
    });

    await page.goto('/vue/resource/create');

    const uploadArea = page.locator('.resourceUploader .v-transmit__upload-area');
    const fileInput = page.locator('.resourceUploader input[type="file"]');
    const selectFileButton = page.getByRole('button', {
        name: 'Datei hier fallen lassen um neue Resource zu erstellen',
    });
    await expect(uploadArea).toBeVisible();
    await expect(uploadArea).toHaveCSS('border-top-style', 'dashed');
    await expect(selectFileButton).toBeVisible();
    await uploadArea.dispatchEvent('dragenter');
    await expect(uploadArea).toHaveClass(/v-transmit__upload-area--is-dragging/);
    await uploadArea.dispatchEvent('dragleave');
    await expect(uploadArea).not.toHaveClass(/v-transmit__upload-area--is-dragging/);
    const autoCreateCheckbox = page.getByRole('checkbox', {name: 'Erstelle Material automatisch'});
    await page.locator('label', {hasText: 'Erstelle Material automatisch'}).click();
    await expect(autoCreateCheckbox).not.toBeChecked();
    await fileInput.setInputFiles([
        {
            name: 'synthetic-upload-a.txt',
            mimeType: 'text/plain',
            buffer: Buffer.from('synthetic upload contents a'),
        },
        {
            name: 'synthetic-upload-b.txt',
            mimeType: 'text/plain',
            buffer: Buffer.from('synthetic upload contents b'),
        },
    ]);
    await expect.poll(() => uploadRequests.length).toBe(2);
    await expect(selectFileButton).toBeVisible();
    expect(uploadRequests.map(request => request.body).join('\n')).toContain('synthetic-upload-a.txt');
    expect(uploadRequests.map(request => request.body).join('\n')).toContain('synthetic-upload-b.txt');
    expect(uploadRequests[0].csrf).toBe('synthetic-csrf-token');

    rejectUpload = true;
    await page.goto('/vue/resource/create');
    const reloadedCheckbox = page.getByRole('checkbox', {name: 'Erstelle Material automatisch'});
    await page.locator('label', {hasText: 'Erstelle Material automatisch'}).click();
    await expect(reloadedCheckbox).not.toBeChecked();
    await fileInput.setInputFiles({
        name: 'rejected-upload.txt',
        mimeType: 'text/plain',
        buffer: Buffer.from('synthetic rejected upload'),
    });
    await expect(page.locator('.resourceUploader .alert-danger')).toBeVisible();
    await expect(page.locator('.resourceUploader .errorStatusCode')).toContainText('422');
    await expect(page.locator('.resourceUploader .errorMessage')).toContainText('Error during upload');
    await page.getByRole('button', {name: 'nochmal versuchen'}).click();
    await expect(selectFileButton).toBeVisible();

    rejectUpload = false;
    await fileInput.setInputFiles({
        name: 'successful-retry.txt',
        mimeType: 'text/plain',
        buffer: Buffer.from('synthetic successful retry'),
    });
    await expect.poll(() => uploadRequests.length).toBe(4);
    await expect(selectFileButton).toBeVisible();
    expect(uploadRequests[3].body).toContain('successful-retry.txt');
    expect(pageErrors).toEqual([]);
});
