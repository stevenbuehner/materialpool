import {expect, test} from '@playwright/test';
import {Buffer} from 'node:buffer';
import {installRalewayFixture} from './ralewayFixture.mjs';
import {saveReadmeScreenshot} from './readmeScreenshot.mjs';
import {viteScriptTag, viteStylesheetTags} from './viteAssets.js';

const compatWarningsByPage = new WeakMap();

test.beforeEach(async ({page}) => {
    const compatWarnings = [];
    compatWarningsByPage.set(page, compatWarnings);
    await installRalewayFixture(page);
    page.on('console', message => {
        if (message.type() === 'warning' && message.text().startsWith('[Vue warn]: (deprecation ')) {
            compatWarnings.push(message.text());
        }
    });
});

test.afterEach(({page}) => {
    expect(compatWarningsByPage.get(page)).toEqual([]);
});

async function expectResolvedNavigation(page) {
    await expect(page.locator('nav').first().locator('.b-nav-dropdown').last().locator('.dropdown-toggle'))
        .toContainText('Synthetic User');
}

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
                        ${viteStylesheetTags}
                    </head>
                    <body>
                        <div id="app"></div>
                        <script>
                            window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                            window.materialpool = {store: {materials: []}};
                        </script>
                        ${viteScriptTag}
                    </body>
                </html>`,
        });
    });
    await page.route('**/api/v1/general/options*', route => route.fulfill({
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
    await expectResolvedNavigation(page);
    await expect(page.locator('.homeContainer .title')).toHaveText('MaterialPool Default');
    await expect(page).toHaveScreenshot('compat-app-home.png', {
        animations: 'disabled',
        caret: 'hide',
    });
    await saveReadmeScreenshot(page, testInfo, 'start-navigation-desktop.png');

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
        await saveReadmeScreenshot(
            page,
            testInfo,
            'start-navigation-mobile.png',
            ['mobile-webkit'],
        );
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

    await accountToggle.click();
    await expect(accountMenu).toBeVisible();
    await page.mouse.click(10, page.viewportSize().height - 10);
    await expect(accountMenu).toBeHidden();

    await expect(speedSearch).toHaveClass(/form-control-sm/);
    await speedSearch.fill('Gamma');
    await page.locator('form').filter({has: speedSearch}).getByRole('button', {name: 'Suchen'}).click();
    await expect(page).toHaveURL(/\/vue\/search\/1\*Gamma$/);

    await page.evaluate(() => {
        window.history.pushState({}, '', '/vue/system/shutdown');
        window.dispatchEvent(new PopStateEvent('popstate'));
    });
    const shutdownDialog = page.locator('.modal.show');
    await expect(shutdownDialog.getByRole('heading', {name: 'Achtung'})).toBeVisible();
    const confirmShutdown = shutdownDialog.getByRole('button', {name: 'Ja'});
    await expect(confirmShutdown).toBeFocused();
    await shutdownDialog.getByRole('button', {name: 'Nein'}).click();
    await expect(shutdownDialog).toBeHidden();
    await expect(page).toHaveURL(/\/vue\/search\/1\*Gamma$/);

    await testInfo.attach('vue-compat-warnings', {
        body: JSON.stringify(compatWarnings, null, 2),
        contentType: 'application/json',
    });
    expect(pageErrors).toEqual([]);
    expect(compatWarnings).toEqual([]);
    expect(unexpectedWarnings).toEqual([]);
});

test('Bible reader loads and selects cached translations', async ({page}, testInfo) => {
    const pageErrors = [];
    const bibleListRequests = [];
    const bibleContentRequests = [];
    const bibleSearchRequests = [];
    page.on('pageerror', error => pageErrors.push(error.stack || error.message));

    await page.route('**/vue/**', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html>
            <html lang="de">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Materialpool Bible Store Test</title>
                    ${viteStylesheetTags}
                </head>
                <body>
                    <div id="app"></div>
                    <script>
                        window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                        window.materialpool = {route: '/readbible/1001001-1001002', store: {materials: []}};
                    </script>
                    ${viteScriptTag}
                </body>
            </html>`,
    }));
    await page.route('**/api/v1/general/options*', route => route.fulfill({
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
    await page.route('**/api/v1/bibles?*', route => {
        bibleListRequests.push(route.request().url());
        return route.fulfill({
            contentType: 'application/json',
            body: JSON.stringify({
                data: [
                    {uuid: 'basis', title: 'BasisBibel'},
                    {uuid: 'lut', title: 'Luther 2017'},
                ],
                current_page: 1,
                last_page: 1,
            }),
        });
    });
    await page.route('**/api/v1/biblecontents/**', route => {
        bibleContentRequests.push(route.request().url());
        const isSecondRange = new URL(route.request().url()).pathname.includes('001001003-001001003');
        return route.fulfill({
            contentType: 'application/json',
            body: JSON.stringify({
                bible: {uuid: 'basis', title: 'BasisBibel'},
                verses: isSecondRange ? [
                    {verse: 1001003, bible_id: 1, bibleUuid: 'basis', text: 'Es werde Licht.'},
                ] : [
                    {verse: 1001001, bible_id: 1, bibleUuid: 'basis', text: 'Am Anfang schuf Gott.'},
                    {verse: 1001002, bible_id: 1, bibleUuid: 'basis', text: 'Die Erde war wüst und leer.'},
                ],
            }),
        });
    });
    await page.route('**/pool/search/**', route => {
        const pathname = new URL(route.request().url()).pathname;

        if (pathname === '/pool/search/guess/bibleverses') {
            bibleSearchRequests.push(route.request().postDataJSON());
            return route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify([{id: 3, from: 1001003, to: 1001003, label: '1Mo 1,3'}]),
            });
        }

        return route.fulfill({
            contentType: 'application/json',
            body: JSON.stringify({
                data: [],
                current_page: 1,
                last_page: 1,
                per_page: 20,
                total: 0,
            }),
        });
    });

    await page.goto('/vue/');

    const translation = page.getByTitle('Übersetzung auswählen');
    await expect(translation).toBeVisible();
    await expect.poll(() => bibleListRequests.length).toBe(1);
    await expect.poll(() => bibleContentRequests.length).toBe(1);
    expect(new URL(bibleContentRequests[0]).pathname).toBe('/api/v1/biblecontents/001001001-001001002');
    await expect(page.locator('.bibleTextPortion')).toContainText('Am Anfang schuf Gott.');
    await expect(page.locator('.bibleTextPortion')).toContainText('Die Erde war wüst und leer.');
    await translation.click();
    await page.getByRole('menuitem', {name: 'BasisBibel'}).click();
    await expect(translation).toContainText('BasisBibel');
    await saveReadmeScreenshot(page, testInfo, 'bible-reader-desktop.png');
    const bibleSearch = page.getByPlaceholder('Bibelvers hier eingeben');
    await bibleSearch.fill('1. Mose 1,3');
    await bibleSearch.press('Enter');
    await expect.poll(() => bibleSearchRequests).toEqual([{q: '1. Mose 1,3'}]);
    await expect(page).toHaveURL(/\/vue\/readbible\/1001003-1001003$/);
    await expect(page.locator('.bibleTextPortion')).toContainText('Es werde Licht.');
    await expectResolvedNavigation(page);
    expect(pageErrors).toEqual([]);
});

test('Bible search optimization loads cross references through Pinia', async ({page}) => {
    const pageErrors = [];
    const crossReferenceRequests = [];
    page.on('pageerror', error => pageErrors.push(error.stack || error.message));

    await page.route('**/vue/**', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html>
            <html lang="de">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Materialpool Cross Reference Store Test</title>
                    ${viteStylesheetTags}
                </head>
                <body>
                    <div id="app"></div>
                    <script>
                        window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                        window.materialpool = {route: '/search/1b1001001-1001002', store: {materials: []}};
                    </script>
                    ${viteScriptTag}
                </body>
            </html>`,
    }));
    await page.route('**/api/v1/general/options*', route => route.fulfill({
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
            last_page: 1,
            per_page: 30,
            total: 0,
        }),
    }));
    await page.route('**/api/v2/bibleverses/crossrefs/**', route => {
        crossReferenceRequests.push(route.request().url());
        return route.fulfill({
            contentType: 'application/json',
            body: JSON.stringify({
                data: [{target_from: 1001003, target_to: 1001003, relevance: 77}],
                total: 1,
                next_page_url: null,
            }),
        });
    });
    await page.route('**/api/v1/biblecontents/**', route => {
        const isCrossReference = /\/0*1001003-0*1001003$/.test(new URL(route.request().url()).pathname);
        return route.fulfill({
            contentType: 'application/json',
            body: JSON.stringify({
                bible: {uuid: 'basis', title: 'BasisBibel'},
                verses: [{
                    verse: isCrossReference ? 1001003 : 1001001,
                    bible_id: 1,
                    bibleUuid: 'basis',
                    text: isCrossReference ? 'Es werde Licht.' : 'Am Anfang schuf Gott.',
                }],
            }),
        });
    });

    await page.goto('/vue/');

    const searchTag = page.locator('.searchInputSelect .sb-search-input-tag').filter({hasText: '1Mo 1,1f'});
    await expect(searchTag).toBeVisible();
    await page.locator('.row .btn-group button').first().click();

    const modal = page.locator('.modal.show');
    await expect(modal.getByRole('heading', {name: 'Schlagwortoptimierung'})).toBeVisible();
    await expect.poll(() => crossReferenceRequests.length).toBeGreaterThan(0);
    await expect(modal.locator('.suggestions .sb-search-input-tag')).toContainText('1Mo 1,3');
    await expect(modal.locator('.suggestions')).toContainText('Es werde Licht.');
    expect(new URL(crossReferenceRequests[0]).pathname)
        .toBe('/api/v2/bibleverses/crossrefs/001001001-001001002');
    expect(pageErrors).toEqual([]);
});

test('Keyword search optimization loads suggestions through Pinia', async ({page}) => {
    const pageErrors = [];
    const suggestionRequests = [];
    page.on('pageerror', error => pageErrors.push(error.stack || error.message));

    await page.route('**/vue/**', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html>
            <html lang="de">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Materialpool Keyword Suggestion Store Test</title>
                    ${viteStylesheetTags}
                </head>
                <body>
                    <div id="app"></div>
                    <script>
                        window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                        window.materialpool = {route: '/search/1k55', store: {materials: []}};
                    </script>
                    ${viteScriptTag}
                </body>
            </html>`,
    }));
    await page.route('**/api/**', async route => {
        const pathname = new URL(route.request().url()).pathname;

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

        if (pathname === '/api/v1/keywords/55') {
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    id: 55,
                    title: 'Primary keyword',
                    lc_title: 'primary keyword',
                    type: 'key',
                    ancestors: [],
                    descendants: [],
                }),
            });
            return;
        }

        if (pathname === '/api/v2/keywords/suggestions/55') {
            suggestionRequests.push(route.request().url());
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    data: [{
                        id: 56,
                        title: 'Related keyword',
                        lc_title: 'related keyword',
                        type: 'key',
                        relevance: 12,
                        ancestors: [],
                        descendants: [],
                    }],
                    total: 1,
                    next_page_url: null,
                }),
            });
            return;
        }

        await route.fulfill({contentType: 'application/json', body: '[]'});
    });
    await page.route('**/pool/search/get?*', route => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
            data: [],
            current_page: 1,
            last_page: 1,
            per_page: 30,
            total: 0,
        }),
    }));

    await page.goto('/vue/');

    const searchTag = page.locator('.searchInputSelect .sb-search-input-tag').filter({hasText: 'Primary keyword'});
    await expect(searchTag).toBeVisible();
    await page.locator('.row .btn-group button').first().click();

    const modal = page.locator('.modal.show');
    await expect(modal.getByRole('heading', {name: 'Schlagwortoptimierung'})).toBeVisible();
    await expect(modal.locator('.keyword-listing .cross-refs')).toHaveText('1');
    await expect.poll(() => suggestionRequests.length).toBe(1);
    await expect(modal.locator('.suggestions .sb-search-input-tag')).toContainText('Related keyword');
    await expect(modal.locator('.suggestions')).toContainText('Relevanz: 12');
    expect(new URL(suggestionRequests[0]).pathname).toBe('/api/v2/keywords/suggestions/55');
    expect(pageErrors).toEqual([]);
});

test('Bundle overview loads and completes an update through Pinia', async ({page}, testInfo) => {
    const pageErrors = [];
    const updateRequests = [];
    page.on('pageerror', error => pageErrors.push(error.stack || error.message));

    await page.route('**/vue/**', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html>
            <html lang="de">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Materialpool Bundle Store Test</title>
                    ${viteStylesheetTags}
                </head>
                <body>
                    <div id="app"></div>
                    <script>
                        window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                        window.materialpool = {route: '/bundle', store: {materials: []}};
                    </script>
                    ${viteScriptTag}
                </body>
            </html>`,
    }));
    await page.route('**/api/**', async route => {
        const pathname = new URL(route.request().url()).pathname;

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

        if (pathname === '/api/v1/bundles' && route.request().method() === 'GET') {
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    bundles: [{
                        id: 7,
                        uuid: 'compat-bundle',
                        name: 'Compat Bundle',
                        author: 'Synthetic Author',
                        description: 'Synthetic bundle description',
                        installed_version: '1.0',
                        update_available: true,
                        is_installed: true,
                        updated_at: '2026-09-01 12:00:00',
                    }],
                    infos: [{
                        uuid: 'compat-bundle',
                        version: '2.0',
                        count_materials: 3,
                        count_files: 4,
                        exportDate: '2026-09-02 12:00:00',
                    }],
                }),
            });
            return;
        }

        if (pathname === '/api/v1/bundles/7/init-update') {
            updateRequests.push({kind: 'init', options: route.request().postDataJSON()});
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    deleteJobs: 0,
                    updateJobs: 1,
                    deletedJobs: 0,
                    openJobs: 1,
                    continueUpdate: false,
                    updateAvailable: true,
                }),
            });
            return;
        }

        if (pathname === '/api/v1/bundles/7/run-update') {
            updateRequests.push({kind: 'run', options: route.request().postDataJSON()});
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    done: 1,
                    open: 0,
                    bundle: {
                        id: 7,
                        uuid: 'compat-bundle',
                        name: 'Compat Bundle',
                        author: 'Synthetic Author',
                        description: 'Synthetic bundle description',
                        installed_version: '2.0',
                        update_available: false,
                        is_installed: true,
                        updated_at: '2026-09-03 12:00:00',
                    },
                }),
            });
            return;
        }

        await route.fulfill({contentType: 'application/json', body: '[]'});
    });

    await page.goto('/vue/');

    const bundleCard = page.locator('.card').filter({hasText: 'Compat Bundle'});
    await expect(bundleCard).toContainText('Version 1.0');
    await expect(bundleCard).toContainText('3 Materialien');
    const updateButton = bundleCard.getByRole('button', {name: /update auf V2\.0 durchführen/i});
    await saveReadmeScreenshot(page, testInfo, 'bundle-management-desktop.png');
    await updateButton.click();
    await expect.poll(() => updateRequests.map(({kind}) => kind)).toEqual(['init', 'run']);
    await expect(bundleCard).toContainText('Version 2.0');
    await expect(updateButton).toHaveCount(0);
    await expectResolvedNavigation(page);
    expect(updateRequests.map(({options}) => options)).toEqual([{}, {}]);
    expect(pageErrors).toEqual([]);
});

test('Keyword tree loads, filters and force-refreshes through Pinia', async ({page}, testInfo) => {
    const pageErrors = [];
    const keywordIndexRequests = [];
    page.on('pageerror', error => pageErrors.push(error.stack || error.message));

    await page.route('**/vue/**', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html>
            <html lang="de">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Materialpool Keyword Store Test</title>
                    ${viteStylesheetTags}
                </head>
                <body>
                    <div id="app"></div>
                    <script>
                        window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                        window.materialpool = {route: '/keyword', store: {materials: []}};
                    </script>
                    ${viteScriptTag}
                </body>
            </html>`,
    }));
    await page.route('**/api/**', async route => {
        const pathname = new URL(route.request().url()).pathname;

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

        if (pathname === '/api/v1/keywords/') {
            keywordIndexRequests.push(route.request().url());
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    data: [{
                        id: 55,
                        title: 'Compat keyword',
                        lc_title: 'compat keyword',
                        type: 'key',
                        parent_id: null,
                    }],
                    current_page: 1,
                    last_page: 1,
                }),
            });
            return;
        }

        await route.fulfill({contentType: 'application/json', body: '[]'});
    });

    await page.goto('/vue/');

    const keywordNode = page.locator('.sbTreeNode:not(.hasChildren)').filter({hasText: 'Compat keyword'});
    await expect(keywordNode).toBeVisible();
    await expect.poll(() => keywordIndexRequests.length).toBe(1);
    const treeSearch = page.getByPlaceholder('Suchen', {exact: true});
    await treeSearch.fill('Compat');
    await expect(keywordNode).toBeVisible();
    await saveReadmeScreenshot(page, testInfo, 'keyword-management-desktop.png');
    await treeSearch.fill('Nicht vorhanden');
    await expect(treeSearch).toHaveValue('Nicht vorhanden');
    expect(pageErrors).toEqual([]);
    await expect(keywordNode).toHaveCount(0);
    await page.locator('.refreshIcon').click();
    await expect.poll(() => keywordIndexRequests.length).toBe(2);
    await expectResolvedNavigation(page);
    expect(pageErrors).toEqual([]);
});

test('Vue 3 datepicker keeps the German input and calendar interaction', async ({page}, testInfo) => {
    const pageErrors = [];
    const attachRequests = [];
    const relevanceRequests = [];
    const usageCreateRequests = [];
    const usageUpdateRequests = [];
    const usageDeleteRequests = [];
    const userSearchRequests = [];
    let returnResourceSuggestion = false;
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
                        ${viteStylesheetTags}
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
                        ${viteScriptTag}
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
                    foreign_ids: [{
                        id: 1,
                        foreign_id: 'external-material-id',
                        bundle_id: null,
                    }],
                }),
            });
            return;
        }

        if (pathname === '/api/v1/materials/2') {
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    id: 2,
                    title: 'Compat interactions',
                    description: '',
                    rating: 10,
                    flag: null,
                    author: null,
                    creator: null,
                    from_bot: false,
                    created_at: '2026-09-01 12:00:00',
                    updated_at: '2026-09-01 12:00:00',
                    resources: [],
                    keywords: [{
                        id: 55,
                        title: 'Compat keyword',
                        lc_title: 'compat keyword',
                        type: 'key',
                        pivot: {relevance: 100},
                    }],
                    bibleverses: [],
                    foreign_ids: [],
                }),
            });
            return;
        }

        if (pathname === '/api/v1/material/2/keyword/55' && route.request().method() === 'POST') {
            const payload = route.request().postDataJSON();
            relevanceRequests.push(payload);
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    id: 55,
                    title: 'Compat keyword',
                    lc_title: 'compat keyword',
                    type: 'key',
                    pivot: {relevance: payload.relevance},
                }),
            });
            return;
        }

        if (pathname === '/api/v1/keywords/55' && route.request().method() === 'GET') {
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    id: 55,
                    title: 'Compat keyword',
                    lc_title: 'compat keyword',
                    type: 'key',
                    descendants: [],
                    ancestors: [],
                }),
            });
            return;
        }

        if (pathname === '/api/v1/resources/find') {
            const resources = returnResourceSuggestion ? [{
                id: 99,
                type: 'text',
                notes: 'Synthetic selectable resource',
                content: 'Selected resource content',
                original_filename: 'selected-resource.txt',
                materials: [],
            }] : [];
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    data: resources,
                    current_page: 1,
                    last_page: 1,
                    per_page: 20,
                    total: resources.length,
                }),
            });
            return;
        }

        if (pathname === '/api/v2/users/find') {
            userSearchRequests.push(new URL(route.request().url()).searchParams.get('s'));
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify([{
                    id: 2,
                    name: 'Second User',
                    email: 'second@example.invalid',
                }]),
            });
            return;
        }

        if (pathname === '/api/v2/material/1/usage' && route.request().method() === 'POST') {
            usageCreateRequests.push(route.request().postDataJSON());
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    id: 71,
                    material_id: 1,
                    datetime: '2026-09-10T10:00:00+02:00',
                    place: '',
                    reason: '',
                    used_by: {
                        id: 1,
                        name: 'Synthetic User',
                        email: 'synthetic@example.invalid',
                    },
                }),
            });
            return;
        }

        if (pathname === '/api/v2/material/1/usage/71' && route.request().method() === 'POST') {
            const payload = route.request().postDataJSON();

            if (payload._method === 'DELETE') {
                usageDeleteRequests.push(payload);
                await route.fulfill({contentType: 'application/json', body: JSON.stringify({success: true})});
            } else {
                usageUpdateRequests.push(payload);
                await route.fulfill({
                    contentType: 'application/json',
                    body: JSON.stringify({
                        id: 71,
                        material_id: 1,
                        datetime: payload.datetime,
                        place: payload.place,
                        reason: payload.reason,
                        used_by: {
                            id: payload.used_by_id,
                            name: 'Second User',
                            email: 'second@example.invalid',
                        },
                    }),
                });
            }
            return;
        }

        if (pathname === '/api/v2/material/1/resource/99/attach' && route.request().method() === 'POST') {
            attachRequests.push(route.request().postData() || '');
            const selectedResource = {
                id: 99,
                type: 'text',
                notes: 'Synthetic selectable resource',
                content: 'Selected resource content',
                original_filename: 'selected-resource.txt',
                remote_path: '',
                is_public: false,
                filesize: 512,
                materials: [],
            };
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    material: {
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
                        resources: [selectedResource],
                        keywords: [],
                        bibleverses: [],
                        foreign_ids: [],
                    },
                    resource: selectedResource,
                }),
            });
            return;
        }

        await route.fulfill({contentType: 'application/json', body: '[]'});
    });
    await page.route('**/pool/search/**', route => {
        const url = new URL(route.request().url());
        const languageOptions = url.searchParams.get('t') === 'lang'
            ? [
                {id: 1, title: 'Deutsch', type: 'lang', pivot: {relevance: 100}},
                {id: 2, title: 'Englisch', type: 'lang', pivot: {relevance: 100}},
            ]
            : [];

        return route.fulfill({
            contentType: 'application/json',
            body: JSON.stringify({
                data: languageOptions,
                current_page: 1,
                last_page: 1,
                per_page: 20,
                total: languageOptions.length,
            }),
        });
    });

    await page.goto('/vue/');

    const dateInput = page.getByPlaceholder('Datum');
    await expect(dateInput).toBeVisible();
    await expect(dateInput).toHaveValue('01.09.2026');
    await expectResolvedNavigation(page);
    await expect(page.getByText('Zugeordnete Bundles', {exact: true})).toHaveCount(0);

    await saveReadmeScreenshot(page, testInfo, 'material-detail-desktop.png');
    if (process.env.MATERIALPOOL_README_SCREENSHOTS !== '1') {
        await expect(page).toHaveScreenshot('material-detail.png', {
            animations: 'disabled',
            caret: 'hide',
        });
    }

    const languageSelect = page.locator('.tagEditSidebarField').filter({hasText: 'Sprachen'});
    await languageSelect.locator('.multiselect-wrapper').click();
    const languageDropdown = languageSelect.locator('.multiselect-dropdown');
    const languageOptions = languageDropdown.locator('.multiselect-options');
    const languageFooter = languageDropdown.locator('.loader').filter({hasText: 'Keine weiteren Ergebnisse'});
    await expect(languageOptions.getByText('Deutsch')).toBeVisible();
    await expect(languageFooter).toBeVisible();
    await expect(page.locator('[class*="vs__"], [class*="vs--"], .v-select')).toHaveCount(0);
    await expect(languageDropdown).toHaveCSS('position', 'absolute');
    await expect(languageOptions).toHaveCSS('position', 'static');
    await expect(languageOptions.getByText('Deutsch')).toHaveCSS('font-size', '12.8px');
    await expect(languageSelect.locator('.multiselect')).toHaveCSS('background-color', 'rgba(0, 0, 0, 0)');
    await expect(languageSelect.locator('.multiselect-wrapper')).toHaveCSS('background-color', 'rgb(255, 255, 255)');
    expect(await languageDropdown.evaluate(dropdown => {
        const options = dropdown.querySelector('.multiselect-options');
        const footer = dropdown.querySelector('.loader:not([style*="display: none"])');

        return options.getBoundingClientRect().top < footer.getBoundingClientRect().top;
    })).toBe(true);

    const titleControl = page.locator('.textEditSidebarField').filter({hasText: 'Titel'}).locator('input');
    const authorControl = page.locator('.singleTagSelect .multiselect-wrapper');
    expect(await authorControl.evaluate(control => control.getBoundingClientRect().height))
        .toBe(await titleControl.evaluate(control => control.getBoundingClientRect().height));
    await expect(authorControl).toHaveCSS('font-size', '12.8px');
    expect(await authorControl.evaluate(wrapper => {
        const caret = wrapper.querySelector('.multiselect-caret');

        return caret.getBoundingClientRect().left > wrapper.getBoundingClientRect().left + wrapper.getBoundingClientRect().width / 2;
    })).toBe(true);
    const clearTitleButton = page.locator('.textEditSidebarField').filter({hasText: 'Titel'}).getByRole('button', {name: 'Eingabefeld leeren'});
    await expect(clearTitleButton).toBeVisible();
    const clearTitleTouchTargetSize = await clearTitleButton.evaluate(button => {
        const style = getComputedStyle(button, '::before');

        return {width: parseFloat(style.width), height: parseFloat(style.height)};
    });
    expect(clearTitleTouchTargetSize.width).toBeGreaterThanOrEqual(44);
    expect(clearTitleTouchTargetSize.height).toBeGreaterThanOrEqual(44);
    await clearTitleButton.click();
    await expect(titleControl).toHaveValue('');
    await expect(titleControl).toBeFocused();

    await expect(page.locator('.selected-relevance').first()).toHaveCSS('background-color', 'rgb(40, 167, 69)');

    const personSelect = page.locator('.tagEditSidebarField').filter({hasText: 'Personen'});
    await personSelect.locator('.multiselect-wrapper').click();
    const minimumCharacterHint = personSelect.locator('.loader').filter({hasText: 'Bitte gib 2 weitere Zeichen ein'});
    await expect(minimumCharacterHint).toBeVisible();
    const minimumCharacterHintStyle = await minimumCharacterHint.evaluate(hint => ({
        color: getComputedStyle(hint).color,
        fontSize: getComputedStyle(hint).fontSize,
        margin: getComputedStyle(hint).margin,
        padding: getComputedStyle(hint).padding,
        textAlign: getComputedStyle(hint).textAlign,
    }));
    const languageFooterStyle = await languageFooter.evaluate(footer => ({
        color: getComputedStyle(footer).color,
        fontSize: getComputedStyle(footer).fontSize,
        margin: getComputedStyle(footer).margin,
        padding: getComputedStyle(footer).padding,
        textAlign: getComputedStyle(footer).textAlign,
    }));
    expect(minimumCharacterHintStyle).toEqual(languageFooterStyle);

    await dateInput.click();
    await expect(page.locator('.vdp-datepicker__calendar').first()).toBeVisible();

    await page.getByTitle('Anlass hinzufügen').click();
    await expect.poll(() => usageCreateRequests.length).toBe(1);
    expect(usageCreateRequests[0]).toMatchObject({place: '', reason: '', used_by_id: 1});

    const usage = page.locator('.usage-edit-list-el').first();
    await expect(usage.getByPlaceholder('Grund')).toBeVisible();
    await usage.getByPlaceholder('Grund').fill('Jugendgruppe');
    await usage.getByPlaceholder('Örtlichkeit').fill('Berlin');
    await usage.locator('.multiselect-clear').click();
    await usage.locator('.used_by .multiselect-wrapper').click();
    await usage.locator('.used_by input').fill('Second');
    await expect.poll(() => userSearchRequests).toContain('Second');
    await usage.locator('.vs__dropdown-option').filter({hasText: 'Second User'}).click();
    await usage.getByTitle('Speichern').click();

    await expect.poll(() => usageUpdateRequests.length).toBe(1);
    expect(usageUpdateRequests[0]).toMatchObject({
        place: 'Berlin',
        reason: 'Jugendgruppe',
        used_by_id: 2,
    });
    await expect(usage.locator('.read-mode')).toContainText('Jugendgruppe');
    await expect(usage.locator('.read-mode')).toContainText('Second User');

    page.once('dialog', dialog => dialog.accept());
    await usage.getByTitle('löschen').click();
    await expect.poll(() => usageDeleteRequests.length).toBe(1);
    expect(usageDeleteRequests[0]).toEqual({_method: 'DELETE'});
    await expect(page.locator('.usage-edit-list-el')).toHaveCount(0);
    for (const closeButton of await page.locator('.flash__close-button').all()) {
        await closeButton.click();
    }
    await expect(page.locator('.flash__message')).toHaveCount(0);

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

    await modal.click({position: {x: 2, y: 2}});
    await expect(modal).toBeHidden();
    await expect(page.locator('.modal-backdrop.show')).toHaveCount(0);
    await expect(page.locator('body')).not.toHaveClass(/\bmodal-open\b/);
    await expect(assignButton).toBeFocused();

    returnResourceSuggestion = true;
    await assignButton.click();
    await expect(modal).toBeVisible();
    await modal.locator('#resourceid').fill('99');
    const selectableResource = modal.locator('li.resource').filter({hasText: 'Synthetic selectable resource'});
    await expect(selectableResource).toBeVisible();
    await selectableResource.click();
    await expect.poll(() => attachRequests.length).toBe(1);
    await expect(modal).toBeHidden();
    await expect(page.locator('body')).not.toHaveClass(/\bmodal-open\b/);
    await expect(page.locator('.resourceDetail')).toContainText('Selected resource content');

    await page.evaluate(() => {
        window.history.pushState({}, '', '/vue/material/2');
        window.dispatchEvent(new PopStateEvent('popstate'));
    });
    const draggableTag = page.locator('.selected-tag.draggable').filter({hasText: 'Compat keyword'});
    await expect(draggableTag).toBeVisible();
    const tagBox = await draggableTag.boundingBox();
    expect(tagBox).not.toBeNull();
    await page.mouse.move(tagBox.x + 2, tagBox.y + (tagBox.height / 2));
    await page.mouse.down();
    await page.mouse.move(tagBox.x + (tagBox.width * 0.75), tagBox.y + (tagBox.height / 2));
    await page.mouse.up();
    await expect.poll(() => relevanceRequests.length).toBe(1);
    expect(relevanceRequests[0]._method).toBe('PUT');
    expect(relevanceRequests[0].relevance).toBeGreaterThan(150);
    expect(relevanceRequests[0].relevance).toBeLessThanOrEqual(300);
    await expect.poll(() => draggableTag.locator('.selected-relevance').evaluate(element =>
        Number.parseFloat(element.style.width)
    )).toBeGreaterThan(50);

    await draggableTag.click({button: 'right'});
    const contextMenu = page.locator('.sb-context-menu');
    await expect(contextMenu).toBeVisible();
    await page.mouse.click(2, 2);
    await expect(contextMenu).toBeHidden();

    await draggableTag.click({button: 'right'});
    await contextMenu.getByRole('link', {name: 'Suche nach "Compat keyword"'}).click();
    await expect(contextMenu).toBeHidden();
    await expect(page).toHaveURL(/\/vue\/search\/1k55$/);
    await expect(page.locator('.searchInputSelect .sb-search-input-tag')).toContainText('Compat keyword');
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
                    ${viteStylesheetTags}
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
                    ${viteScriptTag}
                </body>
            </html>`,
    }));
    await page.route('**/api/v1/general/options*', route => route.fulfill({
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

test('Resource detail cards and multi-page pagination keep their application contracts', async ({page}, testInfo) => {
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
    let resourceFilesize = 1024;
    await page.route('**/vue/**', route => {
        return route.fulfill({
            contentType: 'text/html',
            body: `<!doctype html>
                <html lang="de">
                    <head>
                        <meta charset="utf-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1">
                        <title>Materialpool Resource and Pagination Test</title>
                        ${viteStylesheetTags}
                    </head>
                    <body>
                        <div id="app"></div>
                        <script>
                            window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                            window.materialpool = {route: ${JSON.stringify(initialRoute)}, store: {materials: []}};
                        </script>
                        ${viteScriptTag}
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
                    filesize: resourceFilesize,
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
    await expectResolvedNavigation(page);
    await expect(page).toHaveScreenshot('resource-detail.png', {
        animations: 'disabled',
        caret: 'hide',
    });
    await saveReadmeScreenshot(page, testInfo, 'resource-detail-desktop.png');

    resourceFilesize = null;
    await page.reload();
    const reloadedResourceCard = page.locator('.resource > .card');
    await reloadedResourceCard.getByRole('tab', {name: 'MetaInfo'}).click();
    await expect(reloadedResourceCard.locator('.filesize')).toHaveText('Dateigröße: noch nicht ausgerechnet');
    await expect(reloadedResourceCard.getByRole('tabpanel').locator('.list-group-item').filter({hasText: 'Dateigröße'}))
        .toContainText('noch nicht ausgerechnet');

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

test('Vue 3 uploader keeps multipart success and error handling', async ({page}, testInfo) => {
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
                    ${viteStylesheetTags}
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
                    ${viteScriptTag}
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
    await saveReadmeScreenshot(page, testInfo, 'resource-upload-desktop.png');
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

test('Material creator keeps preset selection, material preload and preset storage', async ({page}) => {
    const pageErrors = [];
    const consoleErrors = [];
    const unexpectedWarnings = [];
    const settingsRequests = [];
    let currentUser = {
        id: 1,
        name: 'Synthetic User',
        email: 'synthetic@example.invalid',
        is_admin: false,
        frontend_user_settings: {
            assign: {
                material: {
                    templates: {
                        Existing: {
                            title: 'Existing preset title',
                            description: 'Existing preset description',
                            rating: 12,
                            author: null,
                            keywords: [],
                            bibleverses: [],
                        },
                    },
                    defaulttemplate: null,
                },
            },
        },
    };

    page.on('pageerror', error => pageErrors.push(error.stack || error.message));
    page.on('console', message => {
        if (message.type() === 'error') consoleErrors.push(message.text());
        if (message.type() === 'warning' && !message.text().startsWith('[Vue warn]: (deprecation ')) {
            unexpectedWarnings.push(message.text());
        }
    });

    await page.route('**/vue/**', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html>
            <html lang="de">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Materialpool Preset Test</title>
                    ${viteStylesheetTags}
                </head>
                <body>
                    <div id="app"></div>
                    <script>
                        window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                        window.materialpool = {route: '/resource/42', store: {materials: []}};
                    </script>
                    ${viteScriptTag}
                </body>
            </html>`,
    }));
    await page.route('**/api/**', async route => {
        const request = route.request();
        const pathname = new URL(request.url()).pathname;

        if (pathname === '/api/v1/general/options') {
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    systemname: 'MaterialPool Default',
                    server: {max_upload: 10485760},
                    user: currentUser,
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
                    content: 'Synthetic resource content',
                    notes: '',
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

        if (pathname === '/api/v1/materials/77') {
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({
                    id: 77,
                    title: 'Material 77 title',
                    description: 'Material 77 description',
                    rating: 16,
                    author: null,
                    keywords: [],
                    bibleverses: [],
                    resources: [],
                }),
            });
            return;
        }

        if (pathname === '/api/v1/users/1' && request.method() === 'POST') {
            const payload = request.postDataJSON();
            settingsRequests.push(payload);
            currentUser = {...currentUser, frontend_user_settings: payload.data};
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify(currentUser),
            });
            return;
        }

        await route.fulfill({contentType: 'application/json', body: '[]'});
    });

    await page.goto('/vue/');
    const resourceCard = page.locator('.resource > .card');
    await resourceCard.getByRole('tab', {name: 'Materialien'}).click();
    const creatorButton = page.getByTitle('Erstelle zugehöriges Material');
    await creatorButton.click();

    const modal = page.locator('.modal.show');
    await expect(modal.getByRole('heading', {name: 'Material erstellen'})).toBeVisible();
    const templateDropdown = modal.locator('.b-dropdown');
    const templateToggle = templateDropdown.locator('.dropdown-toggle');
    const templateMenu = templateDropdown.locator('.dropdown-menu');
    await templateToggle.click();
    await expect(templateMenu).toBeVisible();
    await expect(templateMenu.getByRole('menuitem', {name: /Existing/})).toBeVisible();
    const [modalBox, menuBox] = await Promise.all([modal.boundingBox(), templateMenu.boundingBox()]);
    expect(menuBox.x + menuBox.width).toBeLessThanOrEqual(modalBox.x + modalBox.width);
    await expectResolvedNavigation(page);
    await expect(page).toHaveScreenshot('material-creator-presets.png', {
        animations: 'disabled',
        caret: 'hide',
    });

    await templateMenu.getByRole('menuitem', {name: /Existing/}).click();
    await expect(modal.locator('#materialtitle')).toHaveValue('Existing preset title');
    await expect(modal.locator('#materialdescription')).toHaveValue('Existing preset description');

    await templateToggle.click();
    await templateMenu.getByRole('menuitem', {name: 'Lade von Material-ID'}).click();
    const materialIdInput = templateMenu.getByPlaceholder('Material ID');
    await expect(materialIdInput).toBeFocused();
    await materialIdInput.fill('77');
    await templateMenu.getByRole('button', {name: 'Ok'}).click();
    await expect(templateMenu).toBeHidden();
    await expect(templateToggle).toBeFocused();
    await expect(modal.locator('#materialtitle')).toHaveValue('Material 77 title');
    await expect(modal.locator('#materialdescription')).toHaveValue('Material 77 description');

    await templateToggle.click();
    await templateMenu.getByRole('menuitem', {name: 'Neue Vorlage erstellen'}).click();
    const templateNameInput = templateMenu.getByPlaceholder('Template Name');
    await expect(templateNameInput).toBeFocused();
    await templateNameInput.fill('Saved copy');
    await templateMenu.getByRole('button', {name: 'Speichern'}).click();
    await expect.poll(() => settingsRequests.length).toBe(1);
    await expect(templateMenu).toBeHidden();
    await expect(templateToggle).toBeFocused();
    expect(settingsRequests[0].data.assign.material.templates['Saved copy']).toMatchObject({
        title: 'Material 77 title',
        description: 'Material 77 description',
        rating: 16,
    });

    expect(pageErrors).toEqual([]);
    expect(consoleErrors).toEqual([]);
    expect(unexpectedWarnings).toEqual([]);
});

test('Assign app keeps page selection, attachment and nested image dialogs', async ({page}, testInfo) => {
    const pageErrors = [];
    const consoleErrors = [];
    const unexpectedWarnings = [];
    const attachRequests = [];
    const resourceTagsRequests = [];
    const resourceLoads = [];
    const partialMaterial = {
        id: 9,
        title: 'Partial synthetic material',
        description: '',
        rating: 10,
        author: null,
        keywords: [],
        bibleverses: [],
        resources: [],
        pivot: {limitation: {pages: [2]}},
    };
    let currentResource = {
        id: 42,
        type: 'pdf',
        original_filename: 'synthetic-pages.pdf',
        notes: '',
        remote_path: '',
        is_public: false,
        page_count: 3,
        materials: [partialMaterial],
        creator: null,
        created_at: '2026-09-01 12:00:00',
        updated_at: '2026-09-02 13:00:00',
        content_hash: 'synthetic-pdf-hash',
        filesize: 3072,
    };

    page.on('pageerror', error => pageErrors.push(error.stack || error.message));
    page.on('console', message => {
        if (message.type() === 'error') consoleErrors.push(message.text());
        if (message.type() === 'warning' && !message.text().startsWith('[Vue warn]: (deprecation ')) {
            unexpectedWarnings.push(message.text());
        }
    });

    await page.route('**/vue/**', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html>
            <html lang="de">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Materialpool Assign Test</title>
                    ${viteStylesheetTags}
                </head>
                <body>
                    <div id="app"></div>
                    <script>
                        window.Laravel = {csrfToken: 'synthetic-csrf-token'};
                        window.materialpool = {route: '/resource/42/assign', store: {materials: []}};
                    </script>
                    ${viteScriptTag}
                </body>
            </html>`,
    }));
    await page.route('**/resource/*/image/page-*', async route => {
        const pageNumber = new URL(route.request().url()).pathname.split('-').pop();
        await route.fulfill({
            contentType: 'image/svg+xml',
            body: `<svg xmlns="http://www.w3.org/2000/svg" width="600" height="800">
                <rect width="600" height="800" fill="#f8f9fa"/>
                <rect x="28" y="28" width="544" height="744" fill="#fff" stroke="#adb5bd" stroke-width="4"/>
                <text x="300" y="390" text-anchor="middle" font-family="sans-serif" font-size="72" fill="#495057">Seite ${pageNumber}</text>
            </svg>`,
        });
    });
    await page.route('**/api/**', async route => {
        const request = route.request();
        const pathname = new URL(request.url()).pathname;

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
            resourceLoads.push(42);
            await route.fulfill({contentType: 'application/json', body: JSON.stringify(currentResource)});
            return;
        }

        if (pathname === '/api/v1/resources/43') {
            resourceLoads.push(43);
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({...currentResource, id: 43, page_count: 1, materials: []}),
            });
            return;
        }

        if (pathname === '/api/v1/resources/42/pdf-tags' && request.method() === 'POST') {
            resourceTagsRequests.push(request.postDataJSON());
            await route.fulfill({contentType: 'application/json', body: '[]'});
            return;
        }

        if (pathname === '/api/v2/material/9/resource/42/attach' && request.method() === 'POST') {
            const payload = request.postDataJSON();
            attachRequests.push(payload);
            const attachedMaterial = {
                ...partialMaterial,
                pivot: {limitation: {pages: [2, 1]}},
            };
            currentResource = {...currentResource, materials: [attachedMaterial]};
            await route.fulfill({
                contentType: 'application/json',
                body: JSON.stringify({material: attachedMaterial, resource: currentResource}),
            });
            return;
        }

        await route.fulfill({contentType: 'application/json', body: '[]'});
    });

    await page.goto('/vue/');
    const subMenu = page.locator('.sub-menu');
    await expect(subMenu).toBeVisible();
    await expect(subMenu.getByRole('link', {name: 'MatPool'})).toHaveAttribute('href', '/vue/resource/42');
    const createButton = subMenu.getByRole('button', {name: 'neu', exact: true});
    const addButton = subMenu.getByRole('button', {name: 'hinzufügen', exact: true});
    await expect(createButton).toBeDisabled();
    await expect(addButton).toBeDisabled();

    const pageCells = page.locator('.content > .row > .cell');
    await expect(pageCells).toHaveCount(3);
    await expect(pageCells.locator('img')).toHaveCount(3);
    await expect(pageCells.locator('img').first()).toBeVisible();
    await pageCells.first().locator('.content-container').click();
    await expect(pageCells.first()).toHaveClass(/\bselected\b/);
    await expect(createButton).toBeEnabled();
    await expect(addButton).toBeEnabled();

    await addButton.click();
    const materialSelector = page.locator('.modal.show');
    await expect(materialSelector.getByRole('heading', {name: 'Wähle ein Material'})).toBeVisible();
    await materialSelector.locator('.lastMaterials .material').filter({hasText: 'Partial synthetic material'}).click();
    await expect.poll(() => attachRequests.length).toBe(1);
    expect(attachRequests[0]).toEqual({limitation: {type: 'page', value: '2,1'}});
    await expect(materialSelector).toBeHidden();

    const selectedMaterialDropdown = subMenu.locator('.b-nav-dropdown')
        .filter({hasText: 'ein Material ausgewählt'});
    await selectedMaterialDropdown.locator('.dropdown-toggle').click();
    await expect(selectedMaterialDropdown.getByRole('button', {name: 'löschen'})).toBeVisible();
    await expect(selectedMaterialDropdown.getByRole('link', {name: 'öffnen'}))
        .toHaveAttribute('href', '/vue/material/9');
    await selectedMaterialDropdown.locator('.dropdown-toggle').click();

    const displayDropdown = subMenu.locator('.b-nav-dropdown').filter({hasText: 'Ansicht'});
    await displayDropdown.locator('.dropdown-toggle').click();
    await displayDropdown.getByRole('menuitem', {name: 'groß'}).click();
    await expect(pageCells.first()).toHaveClass(/\bcol-md-6\b/);
    await expectResolvedNavigation(page);
    await expect(page).toHaveScreenshot('assign-app-pages.png', {
        animations: 'disabled',
        caret: 'hide',
    });
    await saveReadmeScreenshot(page, testInfo, 'pdf-page-assignment-desktop.png');

    await subMenu.getByRole('button', {name: 'alles auswählen'}).click();
    await expect(page.locator('.content > .row > .cell.selected')).toHaveCount(3);
    await expect(subMenu.getByRole('button', {name: 'alles auswählen'})).toHaveCount(0);

    await pageCells.first().locator('.zoom').click();
    const imageModal = page.locator('.modal.show:has(.checked-modal-page)');
    await expect(imageModal.locator('.checked-modal-page')).toContainText('selected pages: 1, 2, 3');
    await expect(imageModal.locator('.modal-body img')).toHaveAttribute('src', '/resource/42/image/page-1');
    await imageModal.locator('.next').click();
    await expect(imageModal.locator('.modal-body img')).toHaveAttribute('src', '/resource/42/image/page-2');
    await expect(page).toHaveScreenshot('assign-app-image-zoom.png', {
        animations: 'disabled',
        caret: 'hide',
    });

    await page.keyboard.press('Control+KeyN');
    await expect.poll(() => resourceTagsRequests.length).toBe(1);
    await expect(page.locator('.modal.show')).toHaveCount(2);
    await expect(page.locator('.modal-backdrop.show')).toHaveCount(2);
    await expect(page.locator('body')).toHaveClass(/\bmodal-open\b/);
    const materialCreator = page.locator('.modal.show').filter({hasText: 'Material erstellen'});
    await expect(materialCreator.getByRole('heading', {name: 'Material erstellen'})).toBeVisible();
    await expect(materialCreator).toHaveCSS('z-index', '1075');
    await expect(imageModal).toHaveCSS('z-index', '1055');
    await materialCreator.getByRole('button', {name: 'Abbrechen'}).click();
    await expect(page.locator('.modal.show')).toHaveCount(1);
    await expect(page.locator('.modal-backdrop.show')).toHaveCount(1);
    await expect(page.locator('body')).toHaveClass(/\bmodal-open\b/);

    await imageModal.locator('.modal-body img').click();
    await expect(page.locator('.modal.show')).toHaveCount(0);
    await expect(page.locator('.modal-backdrop.show')).toHaveCount(0);
    await expect(page.locator('body')).not.toHaveClass(/\bmodal-open\b/);

    await page.evaluate(() => {
        window.history.pushState({}, '', '/vue/resource/43/assign');
        window.dispatchEvent(new PopStateEvent('popstate'));
    });
    await expect.poll(() => resourceLoads).toEqual([42, 43]);
    await expect(subMenu.getByRole('link', {name: 'MatPool'})).toHaveAttribute('href', '/vue/resource/43');
    await expect(pageCells).toHaveCount(1);

    expect(pageErrors).toEqual([]);
    expect(consoleErrors).toEqual([]);
    expect(unexpectedWarnings).toEqual([]);
});
