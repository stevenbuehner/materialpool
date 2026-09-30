import {expect, test} from '@playwright/test';
import {installRalewayFixture} from './ralewayFixture.mjs';
import {viteScriptTag, viteStylesheetTags} from './viteAssets.js';

test.beforeEach(async ({page}) => installRalewayFixture(page));

test('Global admin sees grouped queue jobs and separate failed and batch tabs', async ({page}, testInfo) => {
    let requestCount = 0;
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
            ? [{id: 10, queue: 'default', type: 'App\\Jobs\\Example', status: 'reserved', attempts: 1, created_at: 1780250000, available_at: 1780250000}, {id: 11, queue: 'resource-previews-low', type: 'App\\Jobs\\Preview', status: 'waiting', attempts: 0, created_at: 1780250000, available_at: 1780250000}]
            : tab === 'failed'
                ? [{id: 12, queue: 'default', type: 'App\\Jobs\\Example', connection: 'database', failed_at: '2026-09-30T12:00:00Z'}]
                : [{id: 'batch-1', name: 'bundle:test', queue: null, total_jobs: 4, pending_jobs: 2, failed_jobs: 0, created_at: 1780250000, cancelled_at: null, finished_at: null}];
        return route.fulfill({contentType: 'application/json', body: JSON.stringify({
            summary: {waiting: 1, delayed: 0, reserved: 1, failed: 1},
            data: {data: rows, current_page: 1, last_page: 1, total: rows.length},
            queues: ['default', 'resource-previews-low'], refreshed_at: '2026-09-30T12:00:00Z',
        })});
    });

    await page.goto('/vue/admin/queues');
    await expect(page.getByRole('heading', {name: 'Jobs und Queues'})).toBeVisible();
    await expect(page.getByRole('region', {name: 'default'})).toBeVisible();
    await expect(page.getByRole('region', {name: 'resource-previews-low'})).toBeVisible();
    await expect(page.getByText('Reserviert (möglicherweise laufend)').first()).toBeVisible();
    await page.screenshot({path: testInfo.outputPath('admin-queues.png'), fullPage: true});
    await expect.poll(() => requestCount, {timeout: 7000}).toBeGreaterThan(1);

    await page.getByRole('tab', {name: 'Fehlgeschlagene Jobs'}).click();
    await expect(page.getByRole('columnheader', {name: 'Fehlgeschlagen am'})).toBeVisible();
    await page.getByRole('tab', {name: 'Job-Batches'}).click();
    await expect(page.getByRole('region', {name: 'Queue unbekannt'})).toBeVisible();
    await expect(page.getByText('2 / 4')).toBeVisible();
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
    expect(overflow).toBe(false);
});
