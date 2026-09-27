import {Buffer} from 'node:buffer';
import {readFileSync} from 'node:fs';
import path from 'node:path';

const fontUrl = 'https://fonts.gstatic.com/materialpool-tests/raleway-latin.woff2';
const fontContents = Buffer.from(
    readFileSync(path.join(
        process.cwd(),
        'tests/browser/fixtures/raleway-latin.woff2.base64',
    ), 'utf8')
        .replace(/\s+/g, ''),
    'base64',
);

// The landing page requests weight 100 although production imports start at
// 300. Map 100 to the same webfont so an installed host font cannot take over.
const fontFaceCss = [100, 300, 400, 600]
    .map(weight => `
        @font-face {
            font-family: 'Raleway';
            font-style: normal;
            font-weight: ${weight};
            src: url('${fontUrl}') format('woff2');
        }
    `)
    .join('\n');

/**
 * Replaces the external Google Fonts requests with the same Latin Raleway
 * asset. This keeps visual tests independent from network and host fonts.
 */
export async function installRalewayFixture(page) {
    await page.route('https://fonts.googleapis.com/**', route => route.fulfill({
        body: fontFaceCss,
        contentType: 'text/css',
        status: 200,
    }));
    await page.route(fontUrl, route => route.fulfill({
        body: fontContents,
        contentType: 'font/woff2',
        status: 200,
    }));
}
