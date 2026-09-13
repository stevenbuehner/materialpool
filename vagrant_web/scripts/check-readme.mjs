import {existsSync, readFileSync} from 'node:fs';
import {dirname, resolve} from 'node:path';
import {fileURLToPath} from 'node:url';

const repositoryRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const readmePath = resolve(repositoryRoot, 'README.md');
const readme = readFileSync(readmePath, 'utf8');
const errors = [];

const requiredHeadings = [
    '## 1. Administration',
    '### 1.1 Installation',
    '### 1.2 Updates',
    '### 1.3 Wartung',
    '## 2. Entwicklung',
    '## 3. Anwendung',
];

let previousIndex = -1;
for (const heading of requiredHeadings) {
    const index = readme.indexOf(heading);
    if (index === -1) {
        errors.push(`Pflichtüberschrift fehlt: ${heading}`);
    } else if (index <= previousIndex) {
        errors.push(`Pflichtüberschrift steht in falscher Reihenfolge: ${heading}`);
    }
    previousIndex = Math.max(previousIndex, index);
}

const numberedMainHeadings = [...readme.matchAll(/^## (\d+)\. /gm)].map(match => match[1]);
if (numberedMainHeadings.join(',') !== '1,2,3') {
    errors.push(`Erwartet werden genau die nummerierten Hauptabschnitte 1,2,3; gefunden: ${numberedMainHeadings.join(',') || 'keine'}`);
}

const screenshotBlockPattern = /<!-- README-SCREENSHOT\n([\s\S]*?)\n-->\n!\[([^\]]+)]\((docs\/readme\/screenshots\/[^)]+)\)/g;
const screenshotIds = new Set();
const screenshotPaths = new Set();
let screenshotCount = 0;

for (const match of readme.matchAll(screenshotBlockPattern)) {
    screenshotCount += 1;
    const metadata = Object.fromEntries(match[1].split('\n').map(line => {
        const separator = line.indexOf(':');
        return separator === -1
            ? [line.trim(), '']
            : [line.slice(0, separator).trim(), line.slice(separator + 1).trim()];
    }));

    for (const field of ['id', 'route', 'state', 'role', 'viewport', 'fixture', 'source', 'refresh']) {
        if (!metadata[field]) errors.push(`Screenshotblock ${metadata.id || '<ohne id>'}: Feld ${field} fehlt.`);
    }

    if (screenshotIds.has(metadata.id)) errors.push(`Doppelte Screenshot-ID: ${metadata.id}`);
    screenshotIds.add(metadata.id);

    if (screenshotPaths.has(match[3])) errors.push(`Doppelt referenzierter Screenshotpfad: ${match[3]}`);
    screenshotPaths.add(match[3]);

    if (!match[2].trim()) errors.push(`Screenshot ${metadata.id}: Alternativtext fehlt.`);
    if (!existsSync(resolve(repositoryRoot, match[3]))) errors.push(`Screenshotdatei fehlt: ${match[3]}`);
}

const rawScreenshotMarkers = (readme.match(/<!-- README-SCREENSHOT/g) || []).length;
if (screenshotCount === 0) errors.push('Keine dokumentierten README-Screenshots gefunden.');
if (rawScreenshotMarkers !== screenshotCount) {
    errors.push(`${rawScreenshotMarkers} Screenshotmarker, aber nur ${screenshotCount} gültige Marker-Bild-Paare gefunden.`);
}

const localLinkPattern = /(?<!!)\[[^\]]+]\((?!https?:|#|mailto:)([^)]+)\)/g;
for (const match of readme.matchAll(localLinkPattern)) {
    const target = match[1].split('#')[0];
    if (target && !existsSync(resolve(repositoryRoot, target))) errors.push(`Lokales Linkziel fehlt: ${target}`);
}

if (errors.length > 0) {
    console.error(`README-Prüfung fehlgeschlagen:\n- ${errors.join('\n- ')}`);
    process.exitCode = 1;
} else {
    console.log(`README-Prüfung erfolgreich: ${screenshotCount} Screenshotblöcke und alle lokalen Linkziele sind gültig.`);
}
