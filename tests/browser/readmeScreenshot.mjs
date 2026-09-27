import {mkdir} from 'node:fs/promises';
import {resolve} from 'node:path';

const outputDirectory = resolve('docs/readme/screenshots');

export async function saveReadmeScreenshot(page, testInfo, filename, projects = ['desktop-webkit']) {
    if (process.env.MATERIALPOOL_README_SCREENSHOTS !== '1' || !projects.includes(testInfo.project.name)) {
        return;
    }

    await mkdir(outputDirectory, {recursive: true});
    await page.screenshot({
        animations: 'disabled',
        caret: 'hide',
        path: resolve(outputDirectory, filename),
    });
}
