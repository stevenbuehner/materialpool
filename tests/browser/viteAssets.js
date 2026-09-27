import {readFileSync} from 'node:fs';
import {resolve} from 'node:path';

const entryName = 'resources/js/apps/main/index.js';
const manifest = JSON.parse(readFileSync(resolve('public/build/manifest.json'), 'utf8'));
const entry = manifest[entryName];

if (!entry) {
    throw new Error(`Vite manifest does not contain ${entryName}. Run npm run build first.`);
}

export const viteStylesheetTags = (entry.css || [])
    .map(file => `<link rel="stylesheet" href="/build/${file}">`)
    .join('\n');
export const viteScriptTag = `<script type="module" src="/build/${entry.file}"></script>`;
