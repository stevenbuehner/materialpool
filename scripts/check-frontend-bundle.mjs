import {gzipSync} from 'node:zlib';
import {readFileSync, readdirSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import path from 'node:path';

const projectRoot = path.resolve(fileURLToPath(new URL('..', import.meta.url)));
const buildDirectory = path.join(projectRoot, 'public/build');

// Budgets are intentionally close to the verified build rather than Vite's
// generic warning threshold. Raising one requires a reviewed size comparison.
export const bundleBudgets = Object.freeze({
    initialJavaScript: 400 * 1024,
    anyJavaScript: 650 * 1024,
    anyJavaScriptGzip: 190 * 1024,
    // Video.js 8 is lazy-loaded with the video preview and retains HLS/DASH
    // support. Its verified 687.4 KiB chunk stays below this dedicated limit.
    videoPreviewJavaScript: 700 * 1024,
    videoPreviewJavaScriptGzip: 210 * 1024,
    globalCss: 300 * 1024,
});

function formatKibibytes(bytes) {
    return `${(bytes / 1024).toFixed(1)} KiB`;
}

function assertWithinBudget(label, size, budget) {
    if (size > budget) {
        throw new Error(
            `${label} is ${formatKibibytes(size)}; budget is ${formatKibibytes(budget)}.`,
        );
    }
}

export function verifyBundle(directory = buildDirectory) {
    const manifestPath = path.join(directory, 'manifest.json');
    const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
    const initialEntry = manifest['resources/js/apps/main/index.js'];
    const globalCssEntry = manifest['resources/sass/main.scss'];

    if (!initialEntry?.file || !globalCssEntry?.file) {
        throw new Error('Vite manifest does not contain the expected frontend entries.');
    }

    const assetDirectory = path.join(directory, 'assets');
    const assetNames = readdirSync(assetDirectory);
    const sourceMaps = assetNames.filter(name => name.endsWith('.map'));

    if (sourceMaps.length > 0) {
        throw new Error(`Production build contains ${sourceMaps.length} public source map(s).`);
    }

    const javascriptFiles = assetNames.filter(name => name.endsWith('.js'));
    let largestJavaScript = {name: '', raw: 0, gzip: 0};

    for (const name of javascriptFiles) {
        const contents = readFileSync(path.join(assetDirectory, name));
        const raw = contents.byteLength;
        const gzip = gzipSync(contents).byteLength;

        const isVideoPreviewChunk = name.startsWith('video-preview-');
        const rawBudget = isVideoPreviewChunk
            ? bundleBudgets.videoPreviewJavaScript
            : bundleBudgets.anyJavaScript;
        const gzipBudget = isVideoPreviewChunk
            ? bundleBudgets.videoPreviewJavaScriptGzip
            : bundleBudgets.anyJavaScriptGzip;

        assertWithinBudget(`${name} (raw)`, raw, rawBudget);
        assertWithinBudget(`${name} (gzip)`, gzip, gzipBudget);

        if (raw > largestJavaScript.raw) {
            largestJavaScript = {name, raw, gzip};
        }
    }

    const initialJavaScript = readFileSync(path.join(directory, initialEntry.file));
    const globalCss = readFileSync(path.join(directory, globalCssEntry.file));

    assertWithinBudget(
        `${initialEntry.file} (initial JavaScript)`,
        initialJavaScript.byteLength,
        bundleBudgets.initialJavaScript,
    );
    assertWithinBudget(
        `${globalCssEntry.file} (global CSS)`,
        globalCss.byteLength,
        bundleBudgets.globalCss,
    );

    return {
        initialJavaScript: initialJavaScript.byteLength,
        globalCss: globalCss.byteLength,
        largestJavaScript,
    };
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
    const result = verifyBundle();

    console.log(
        `Bundle budgets passed: initial JS ${formatKibibytes(result.initialJavaScript)}, `
        + `largest JS ${formatKibibytes(result.largestJavaScript.raw)} `
        + `(${result.largestJavaScript.name}), global CSS ${formatKibibytes(result.globalCss)}.`,
    );
}
