import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

import {afterEach, describe, expect, it} from 'vitest';

import {verifyBundle} from '../../scripts/check-frontend-bundle.mjs';

const temporaryDirectories = [];

function createBuild({initialJavaScript = 'console.log("ok");', extraAssets = {}} = {}) {
    const buildDirectory = fs.mkdtempSync(path.join(os.tmpdir(), 'materialpool-bundle-'));
    const assetDirectory = path.join(buildDirectory, 'assets');
    temporaryDirectories.push(buildDirectory);
    fs.mkdirSync(assetDirectory);

    fs.writeFileSync(path.join(buildDirectory, 'manifest.json'), JSON.stringify({
        'resources/js/apps/main/index.js': {file: 'assets/index.js'},
        'resources/sass/main.scss': {file: 'assets/main.css'},
    }));
    fs.writeFileSync(path.join(assetDirectory, 'index.js'), initialJavaScript);
    fs.writeFileSync(path.join(assetDirectory, 'main.css'), '.app {}');

    for (const [name, contents] of Object.entries(extraAssets)) {
        fs.writeFileSync(path.join(assetDirectory, name), contents);
    }

    return buildDirectory;
}

afterEach(() => {
    for (const directory of temporaryDirectories.splice(0)) {
        fs.rmSync(directory, {recursive: true, force: true});
    }
});

describe('frontend bundle budgets', () => {
    it('accepts a complete build within every budget', () => {
        const result = verifyBundle(createBuild());

        expect(result.largestJavaScript.name).toBe('index.js');
    });

    it('rejects publicly deployed source maps', () => {
        const buildDirectory = createBuild({extraAssets: {'index.js.map': '{}'}});

        expect(() => verifyBundle(buildDirectory)).toThrow(/public source map/);
    });

    it('rejects an oversized initial JavaScript entry', () => {
        const buildDirectory = createBuild({initialJavaScript: Buffer.alloc(401 * 1024)});

        expect(() => verifyBundle(buildDirectory)).toThrow(/initial JavaScript/);
    });
});
