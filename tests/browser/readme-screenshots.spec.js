import {expect, test} from '@playwright/test';
import {installRalewayFixture} from './ralewayFixture.mjs';
import {saveReadmeScreenshot} from './readmeScreenshot.mjs';
import {viteScriptTag, viteStylesheetTags} from './viteAssets.js';

test.beforeEach(async ({page}) => {
    await installRalewayFixture(page);
});

test('search-results', async ({page}, testInfo) => {
    await page.route('**/vue/**', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html>
            <html lang="de">
                <head>
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1">
                    <title>Materialpool Dokumentation</title>
                    ${viteStylesheetTags}
                </head>
                <body>
                    <div id="app"></div>
                    <script>
                        window.Laravel = {csrfToken: 'docs-csrf-token'};
                        window.materialpool = {route: '/search/1*Jugendarbeit', store: {materials: []}};
                    </script>
                    ${viteScriptTag}
                </body>
            </html>`,
    }));

    await page.route('**/api/v1/general/options*', route => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
            systemname: 'Materialpool',
            server: {max_upload: 10485760},
            user: {
                id: 1001,
                name: 'Dokumentationskonto',
                email: 'docs@example.invalid',
                is_admin: false,
                frontend_user_settings: {},
            },
        }),
    }));

    await page.route('**/pool/search/get?*', route => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({
            data: [
                {
                    id: 101,
                    title: 'Ideen für die Jugendgruppe',
                    description: 'Methoden, Gesprächsimpulse und eine Präsentation für einen Gruppenabend.',
                    author: {id: 201, title: 'Redaktion Materialpool'},
                    from_bot: false,
                    icon_of_bundle: null,
                    resources: [{id: 301, type: 'pdf'}, {id: 302, type: 'image'}],
                    keywords: [{id: 401, title: 'Jugendarbeit', type: 'key', pivot: {relevance: 100}}],
                    bibleverses: [{id: 501, from: 43003016, to: 43003017, pivot: {relevance: 80}}],
                },
                {
                    id: 102,
                    title: 'Teamspiele zum Kennenlernen',
                    description: 'Eine kompakte Sammlung kooperativer Spiele für neue Gruppen.',
                    author: null,
                    from_bot: false,
                    icon_of_bundle: null,
                    resources: [{id: 303, type: 'text'}],
                    keywords: [{id: 402, title: 'Gruppenspiele', type: 'key', pivot: {relevance: 90}}],
                    bibleverses: [],
                },
            ],
            current_page: 1,
            from: 1,
            last_page: 2,
            next_page_url: '/pool/search/get?page=2',
            per_page: 30,
            prev_page_url: null,
            to: 2,
            total: 32,
        }),
    }));

    await page.route('**/material/*/preview?*', route => route.fulfill({
        contentType: 'image/svg+xml',
        body: '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect width="320" height="180" fill="#e9ecef"/><text x="160" y="95" text-anchor="middle" font-family="sans-serif" font-size="24" fill="#495057">Vorschau</text></svg>',
    }));

    await page.goto('/vue/');
    await expect(page.getByText('Ideen für die Jugendgruppe')).toBeVisible();
    await expect(page.getByText('Teamspiele zum Kennenlernen')).toBeVisible();
    const preview = page.locator('.materialListingItem .preview .image').first();
    await expect(preview).toHaveJSProperty('naturalWidth', 320);
    await expect(preview).toHaveCSS('object-fit', 'cover');
    await expect(preview).toHaveCSS('object-position', '50% 50%');
    if (testInfo.project.name === 'desktop-webkit') {
        await saveReadmeScreenshot(page, testInfo, 'search-results-desktop.png');
    }
});
