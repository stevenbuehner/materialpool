import { readdir, readFile } from 'node:fs/promises';
import { extname, join, relative } from 'node:path';

const root = process.cwd();
const sourceRoot = join(root, 'resources/js');
const sourceExtensions = new Set(['.js', '.vue']);

async function walk(directory) {
    const entries = await readdir(directory, { withFileTypes: true });
    const paths = await Promise.all(entries.map(async entry => {
        const path = join(directory, entry.name);
        return entry.isDirectory() ? walk(path) : [path];
    }));

    return paths.flat();
}

const files = (await walk(sourceRoot)).filter(path => sourceExtensions.has(extname(path)));
const sources = await Promise.all(files.map(async path => ({
    path: relative(root, path),
    text: await readFile(path, 'utf8'),
})));

const packageImports = new Set();
const importPattern = /(?:import[\s\S]*?from\s*|import\s*|require\s*\()(['"])([^'".][^'"]*)\1/g;

for (const { text } of sources) {
    for (const match of text.matchAll(importPattern)) {
        const specifier = match[2];
        const parts = specifier.split('/');
        packageImports.add(specifier.startsWith('@') ? parts.slice(0, 2).join('/') : parts[0]);
    }
}

const patterns = {
    bootstrapVueImports: /from\s+['"]bootstrap-vue['"]|require\(['"]bootstrap-vue['"]\)/,
    compatUsage: /@vue\/compat|\bconfigureCompat\b|\bcompatConfig\s*:/,
    componentModelOptions: /(?<![\w$])model\s*:\s*\{/,
    // Component $emit calls are normal in Vue 3; only removed instance-bus APIs
    // identify a legacy event bus.
    eventBusUsage: /\$on\s*\(|\$off\s*\(|\$once\s*\(/,
    filters: /\bfilters\s*:/,
    functionalComponents: /\bfunctional\s*:\s*true/,
    legacyDirectiveHooks: /(?<![.$\w])(?:bind|inserted|componentUpdated|unbind)\s*\(/,
    legacyLifecycleHooks: /\b(?:beforeDestroy|destroyed)\s*(?:\(|:)/,
    renderFunctions: /\brender\s*\(/,
    runtimeTemplates: /\btemplate\s*:\s*[`'"]/,
    scopedSlots: /\$scopedSlots\b|slot-scope\s*=/,
    syncModifiers: /\.sync\b/,
    vueSetDelete: /Vue\.(?:set|delete)\s*\(|this\.\$(?:set|delete)\s*\(/,
    vHtml: /\bv-html\s*=/,
};

const patternMatches = Object.fromEntries(Object.entries(patterns).map(([name, pattern]) => [
    name,
    sources.filter(({ text }) => pattern.test(text)).map(({ path }) => path),
]));

const storeIndex = sources.find(({ path }) => path === 'resources/js/apps/main/store/index.js')?.text || '';
const modulesBlock = storeIndex.match(/modules\s*:\s*\{([\s\S]*?)\n\s*\}/)?.[1] || '';
const registeredStoreModules = modulesBlock
    .split(',')
    .map(name => name.trim())
    .filter(name => /^[A-Za-z_$][\w$]*$/.test(name));

const report = {
    generatedAt: new Date().toISOString(),
    sourceFiles: {
        js: files.filter(path => extname(path) === '.js').length,
        vue: files.filter(path => extname(path) === '.vue').length,
        total: files.length,
    },
    externalImports: [...packageImports].sort(),
    registeredStoreModules,
    patterns: Object.fromEntries(Object.entries(patternMatches).map(([name, matches]) => [name, {
        count: matches.length,
        files: matches,
    }])),
};

process.stdout.write(`${JSON.stringify(report, null, 2)}\n`);
