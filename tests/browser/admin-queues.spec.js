import {expect, test} from '@playwright/test';
import {installRalewayFixture} from './ralewayFixture.mjs';
import {viteScriptTag, viteStylesheetTags} from './viteAssets.js';

test.beforeEach(async ({page}) => installRalewayFixture(page));

test('Bundle manager keeps Bundle access without global admin links', async ({page}) => {
    await page.route('**/vue/', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">${viteStylesheetTags}</head><body><div id="app"></div><script>window.Laravel = {csrfToken: 'synthetic-csrf-token'}; window.materialpool = {store: {materials: []}};</script>${viteScriptTag}</body></html>`,
    }));
    await page.route('**/api/v1/general/options*', route => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({systemname: 'MaterialPool Default', user: {id: 2, name: 'Bundle Manager', email: 'bundle@example.invalid', is_admin: false, permissions: ['bundles.manage'], frontend_user_settings: {}}}),
    }));

    await page.goto('/vue/');
    if (test.info().project.name === 'mobile-webkit') await page.locator('.navbar-toggler').click();
    await page.getByRole('link', {name: 'Admin', exact: true}).click();
    await expect(page.getByRole('menuitem', {name: 'Bundle'})).toBeVisible();
    await expect(page.getByRole('menuitem', {name: 'Jobs und Queues'})).toHaveCount(0);
    await expect(page.getByText('Kalibrierung', {exact: true})).toHaveCount(0);
    await expect(page.getByRole('link', {name: 'Bearbeiten'})).toHaveCount(0);
});

test('Global admin sees grouped queue jobs and separate failed and batch tabs', async ({page}, testInfo) => {
    let requestCount = 0;
    let jobDetailRequests = 0;
    const jobDetailUrls = [];
    const pageErrors = [];
    page.on('pageerror', error => pageErrors.push(error.message));
    const failedRows = [
        {id: 12, uuid: '00000000-0000-0000-0000-000000000012', queue: 'default', type: 'App\\Jobs\\Example', connection: 'database', can_retry: true, failed_at: '2026-09-30T12:00:00Z'},
        {id: 13, uuid: '00000000-0000-0000-0000-000000000013', queue: 'default', type: 'App\\Jobs\\Example', connection: 'database', can_retry: true, failed_at: '2026-09-30T12:00:00Z'},
    ];
    await page.route('**/vue/admin/queues', route => route.fulfill({
        contentType: 'text/html',
        body: `<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">${viteStylesheetTags}</head><body><div id="app"></div><script>window.Laravel = {csrfToken: 'synthetic-csrf-token'}; window.materialpool = {route: '/admin/queues', store: {materials: []}};</script>${viteScriptTag}</body></html>`,
    }));
    await page.route('**/api/v1/general/options*', route => route.fulfill({
        contentType: 'application/json',
        body: JSON.stringify({systemname: 'MaterialPool Default', user: {id: 1, name: 'Admin', email: 'admin@example.invalid', is_admin: true, permissions: [], frontend_user_settings: {}}}),
    }));
    await page.route('**/api/v2/admin/queue-overview*', route => {
        requestCount++;
        const tab = new URL(route.request().url()).searchParams.get('tab');
        const rows = tab === 'jobs'
            ? [{id: 10, queue: 'default', type: 'App\\Jobs\\Example', status: 'reserved', attempts: 1, created_at: 1780250000, available_at: 1780250000}, {id: 12, queue: 'default', type: 'App\\Jobs\\Alpha', status: 'waiting', attempts: 2, created_at: 1780250100, available_at: 1780250100}, {id: 11, queue: 'resource-previews-low', type: 'App\\Jobs\\Preview', status: 'waiting', attempts: 0, created_at: 1780250000, available_at: 1780250000}]
            : tab === 'failed'
                ? failedRows
                : [{id: 'batch-1', name: 'bundle:test', queue: null, total_jobs: 4, pending_jobs: 2, failed_jobs: 0, created_at: 1780250000, cancelled_at: null, finished_at: null}];
        return route.fulfill({contentType: 'application/json', body: JSON.stringify({
            summary: {waiting: 1, delayed: 0, reserved: 1, failed: 1},
            data: {data: rows, current_page: 1, last_page: 1, total: rows.length},
            queues: ['default', 'resource-previews-low'], refreshed_at: '2026-09-30T12:00:00Z',
        })});
    });
    await page.route('**/api/v2/admin/queue-overview/failed/**', route => {
        const url = new URL(route.request().url());
        const uuid = url.pathname.split('/')[6];
        const job = failedRows.find(row => row.uuid === uuid);
        if (!job) return route.fulfill({status: 404, contentType: 'application/json', body: '{}'});
        if (route.request().method() === 'GET') return route.fulfill({contentType: 'application/json', body: JSON.stringify({...job, payload: {title: 'Grüße', nested: {count: 3}, secret: '[redacted]'}, exception: 'RuntimeException: synthetic-exception\nStack trace:\n#0 /var/www/app/Jobs/Example.php(20): handle()\n#1 /var/www/vendor/laravel/framework/Worker.php(12): run()'})});
        failedRows.splice(failedRows.indexOf(job), 1);
        return route.fulfill({contentType: 'application/json', body: JSON.stringify({message: 'Aktion erfolgreich'})});
    });
    await page.route('**/api/v2/admin/queue-overview/jobs/**', route => {
        jobDetailRequests++;
        jobDetailUrls.push(route.request().url());
        if (new URL(route.request().url()).pathname.endsWith('/12')) return route.fulfill({status: 404, contentType: 'application/json', body: JSON.stringify({message: 'Dieser Job ist nicht mehr in der Queue vorhanden.'})});
        return route.fulfill({contentType: 'application/json', body: JSON.stringify({id: 10, queue: 'default', type: 'App\\Jobs\\Example', status: 'reserved', attempts: 1, created_at: 1780250000, available_at: 1780250000, payload: {data: {jobData: {title: 'Grüße', nested: {count: 3}, secret: '[redacted]'}}}})});
    });
    page.on('dialog', dialog => dialog.accept());

    await page.goto('/vue/admin/queues');
    await expect(page.getByRole('heading', {name: 'Jobs und Queues'})).toBeVisible();
    const refreshButton = page.getByRole('button', {name: /Jetzt aktualisieren/});
    await expect(refreshButton).toContainText(/in [1-5] Sek/);
    const refreshWidth = (await refreshButton.boundingBox()).width;
    await expect(page.getByRole('region', {name: 'default'})).toBeVisible();
    await expect(page.getByRole('region', {name: 'resource-previews-low'})).toBeVisible();
    await expect(page.getByText('Reserviert (möglicherweise laufend)').first()).toBeVisible();
    const defaultQueue = page.getByRole('region', {name: 'default'});
    expect(jobDetailRequests).toBe(0);
    await defaultQueue.getByRole('button', {name: 'Details'}).first().click();
    await expect(page.getByRole('dialog', {name: 'Aktueller Job'})).toBeVisible();
    await expect(page.getByRole('dialog', {name: 'Aktueller Job'}).getByText('Grüße')).toBeVisible();
    expect(jobDetailRequests).toBe(1);
    await page.screenshot({path: testInfo.outputPath('admin-current-job-detail.png'), fullPage: true});
    await page.keyboard.press('Escape');
    await expect(page.getByRole('dialog', {name: 'Aktueller Job'})).toBeHidden();
    await defaultQueue.getByRole('button', {name: 'Details'}).nth(1).click();
    expect(jobDetailUrls.map(url => new URL(url).pathname)).toEqual(['/api/v2/admin/queue-overview/jobs/10', '/api/v2/admin/queue-overview/jobs/12']);
    await expect(page.getByRole('dialog', {name: 'Aktueller Job'}).getByRole('alert')).toHaveText('Dieser Job ist nicht mehr in der Queue vorhanden.');
    await page.keyboard.press('Escape');
    await expect(page.getByRole('dialog', {name: 'Aktueller Job'})).toBeHidden();
    const defaultIds = defaultQueue.locator('tbody tr td:first-child');
    for (const [column, ascending, descending] of [
        ['ID', ['10', '12'], ['12', '10']],
        ['Jobtyp', ['12', '10'], ['10', '12']],
        ['Status', ['10', '12'], ['12', '10']],
        ['Erstellt', ['10', '12'], ['12', '10']],
        ['Verfügbar ab', ['10', '12'], ['12', '10']],
        ['Versuche', ['10', '12'], ['12', '10']],
    ]) {
        await defaultQueue.getByRole('button', {name: `${column} aufsteigend sortieren`}).click();
        await expect(defaultIds).toHaveText(ascending);
        await expect(defaultQueue.getByRole('columnheader', {name: new RegExp(column)})).toHaveAttribute('aria-sort', 'ascending');
        await defaultQueue.getByRole('button', {name: `${column} absteigend sortieren`}).click();
        await expect(defaultIds).toHaveText(descending);
    }
    await expect(page.getByText('Sortierung gilt für die Jobs auf dieser Seite', {exact: false})).toBeVisible();
    await page.getByRole('button', {name: /Jetzt aktualisieren/}).click();
    await expect(defaultIds).toHaveText(['12', '10']);
    await page.screenshot({path: testInfo.outputPath('admin-queues.png'), fullPage: true});
    await expect.poll(() => requestCount, {timeout: 7000}).toBeGreaterThan(1);
    await expect(page.getByRole('button', {name: /Jetzt aktualisieren/})).toBeVisible();
    expect((await refreshButton.boundingBox()).width).toBe(refreshWidth);
    await expect(page.getByText('Letzte Aktualisierung')).toHaveCount(0);

    await page.getByRole('tab', {name: 'Fehlgeschlagene Jobs'}).click();
    await expect(page.getByRole('columnheader', {name: 'Fehlgeschlagen am'})).toBeVisible();
    await page.getByRole('button', {name: 'Details'}).first().click();
    await expect(page.getByText('RuntimeException: synthetic-exception', {exact: true})).toBeVisible();
    await expect(page.getByText('Anwendungsstellen')).toBeVisible();
    await expect(page.getByText('Grüße')).toBeVisible();
    await page.screenshot({path: testInfo.outputPath('admin-failed-detail.png'), fullPage: true});
    await page.getByRole('button', {name: 'Job erneut starten'}).click();
    await expect(page.getByRole('status').getByText('Aktion erfolgreich')).toBeVisible();
    await expect(page.getByRole('button', {name: 'Details'})).toHaveCount(1);
    await page.getByRole('button', {name: 'Details'}).click();
    await page.getByRole('button', {name: 'Fehlgeschlagenen Job löschen'}).click();
    await expect(page.getByText('Keine Einträge für diese Auswahl.')).toBeVisible();
    await page.getByRole('tab', {name: 'Job-Batches'}).click();
    await expect(page.getByRole('region', {name: 'Queue unbekannt'})).toBeVisible();
    await expect(page.getByText('2 / 4')).toBeVisible();
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
    expect(overflow).toBe(false);
    expect(pageErrors).toEqual([]);
});
