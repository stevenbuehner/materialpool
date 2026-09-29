import {existsSync, readFileSync, readdirSync} from 'node:fs';
import {dirname, relative, resolve} from 'node:path';
import {fileURLToPath} from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const readme = readFileSync(resolve(root, 'README.md'), 'utf8');
const applicationPath = resolve(root, 'docs/anwendung.md');
const application = readFileSync(applicationPath, 'utf8');
const errors = [];

for (const target of ['docs/anwendung.md', 'docs/administration.md', 'docs/entwicklung.md', 'deployment/docs/installation.md']) {
    if (!readme.includes(`](${target})`)) errors.push(`README-Einstieg fehlt: ${target}`);
}
if (/^## \d+\. /m.test(readme) || readme.includes('<!-- README-SCREENSHOT')) {
    errors.push('README.md muss eine Übersicht ohne alte Hauptabschnitte und Screenshots bleiben.');
}

const screenshotPattern = /<!-- README-SCREENSHOT\n([\s\S]*?)\n-->\n!\[([^\]]+)]\((readme\/screenshots\/[^)]+)\)/g;
const screenshotIds = new Set();
const screenshotPaths = new Set();
let screenshotCount = 0;
for (const match of application.matchAll(screenshotPattern)) {
    screenshotCount += 1;
    const metadata = Object.fromEntries(match[1].split('\n').map(line => {
        const separator = line.indexOf(':');
        return separator === -1 ? [line.trim(), ''] : [line.slice(0, separator).trim(), line.slice(separator + 1).trim()];
    }));
    for (const field of ['id', 'route', 'state', 'role', 'viewport', 'fixture', 'source', 'refresh']) {
        if (!metadata[field]) errors.push(`Screenshotblock ${metadata.id || '<ohne id>'}: Feld ${field} fehlt.`);
    }
    if (screenshotIds.has(metadata.id)) errors.push(`Doppelte Screenshot-ID: ${metadata.id}`);
    screenshotIds.add(metadata.id);
    if (screenshotPaths.has(match[3])) errors.push(`Doppelt referenzierter Screenshotpfad: ${match[3]}`);
    screenshotPaths.add(match[3]);
    if (!match[2].trim()) errors.push(`Screenshot ${metadata.id}: Alternativtext fehlt.`);
    if (!existsSync(resolve(dirname(applicationPath), match[3]))) errors.push(`Screenshotdatei fehlt: ${match[3]}`);
}
const rawMarkers = (application.match(/<!-- README-SCREENSHOT/g) || []).length;
if (!screenshotCount || rawMarkers !== screenshotCount) {
    errors.push(`${rawMarkers} Screenshotmarker, aber ${screenshotCount} gültige Marker-Bild-Paare.`);
}

function markdownFiles(directory) {
    return readdirSync(directory, {withFileTypes: true}).flatMap(entry => {
        const path = resolve(directory, entry.name);
        return entry.isDirectory() ? markdownFiles(path) : entry.name.endsWith('.md') ? [path] : [];
    });
}

function anchors(markdown) {
    const found = new Set();
    const counts = new Map();
    let fenced = false;
    for (const line of markdown.split('\n')) {
        if (/^\s*(```|~~~)/.test(line)) {
            fenced = !fenced;
            continue;
        }
        if (fenced) continue;
        const heading = line.match(/^#{1,6} (.+)$/);
        if (!heading) continue;
        const slug = heading[1].replace(/<[^>]*>/g, '').replace(/\[([^\]]+)]\([^)]+\)/g, '$1')
            .replace(/[`*_~]/g, '').toLowerCase().replace(/[^\p{L}\p{N} _-]/gu, '')
            .trim().replace(/ +/g, '-');
        const count = counts.get(slug) || 0;
        found.add(count ? `${slug}-${count}` : slug);
        counts.set(slug, count + 1);
    }
    return found;
}

function withoutFencedBlocks(markdown) {
    let fenced = false;
    return markdown.split('\n').map(line => {
        if (/^\s*(```|~~~)/.test(line)) {
            fenced = !fenced;
            return '';
        }
        return fenced ? '' : line;
    }).join('\n');
}

const files = [resolve(root, 'README.md'), ...markdownFiles(resolve(root, 'docs')), ...markdownFiles(resolve(root, 'deployment'))];
const anchorsByFile = new Map(files.map(path => [path, anchors(readFileSync(path, 'utf8'))]));
for (const path of files) {
    const label = relative(root, path);
    const markdown = withoutFencedBlocks(readFileSync(path, 'utf8'));
    for (const match of markdown.matchAll(/!?\[[^\]]+]\(([^)]+)\)/g)) {
        const href = match[1].split(/\s+"/)[0];
        if (/^(https?:|mailto:|data:)/.test(href)) continue;
        const [target, anchor] = href.split('#', 2);
        const targetPath = target ? resolve(dirname(path), decodeURIComponent(target)) : path;
        if (!existsSync(targetPath)) errors.push(`${label}: Linkziel fehlt: ${href}`);
        else if (anchor && anchorsByFile.has(targetPath) && !anchorsByFile.get(targetPath).has(decodeURIComponent(anchor))) {
            errors.push(`${label}: Abschnitt fehlt: ${href}`);
        }
    }
}

if (errors.length) {
    console.error(`Dokumentationsprüfung fehlgeschlagen:\n- ${errors.join('\n- ')}`);
    process.exitCode = 1;
} else {
    console.log(`Dokumentationsprüfung erfolgreich: ${screenshotCount} Screenshots und lokale Links/Abschnitte gültig.`);
}
