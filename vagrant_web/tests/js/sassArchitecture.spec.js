import fs from 'node:fs';
import path from 'node:path';

import * as sass from 'sass';
import {describe, expect, it} from 'vitest';

const projectRoot = process.cwd();
const resourceRoot = path.join(projectRoot, 'resources');

function sourceFiles(directory) {
  return fs.readdirSync(directory, {withFileTypes: true}).flatMap(entry => {
    const entryPath = path.join(directory, entry.name);

    if (entry.isDirectory()) {
      return sourceFiles(entryPath);
    }

    return entryPath.endsWith('.scss') || entryPath.endsWith('.vue')
      ? [entryPath]
      : [];
  });
}

describe('Sass architecture', () => {
  it('keeps application Sass free from legacy Sass imports', () => {
    const legacyImports = [];

    for (const file of sourceFiles(resourceRoot)) {
      const contents = fs.readFileSync(file, 'utf8');
      const imports = contents.matchAll(/@import\s+["']([^"']+)["']/g);

      for (const match of imports) {
        if (!match[1].endsWith('.css')) {
          legacyImports.push(`${path.relative(projectRoot, file)}: ${match[1]}`);
        }
      }
    }

    expect(legacyImports).toEqual([]);
  });

  it('exposes stable Materialpool tokens without loading Bootstrap', () => {
    const themeFile = path.join(resourceRoot, 'sass/theme.scss');
    const themeSource = fs.readFileSync(themeFile, 'utf8');

    expect(themeSource).not.toContain('bootstrap/scss');

    const result = sass.compileString(`
      @use "sass:map";
      @use "resources/sass/theme" as theme;

      .materialpool-token-contract {
        xxl-width: map.get(theme.$container-max-widths, xxl);
        content-cap: theme.$materialpool-container-max-width;
        danger-hover: theme.$red-hover;
      }
    `, {
      loadPaths: [projectRoot],
      style: 'expanded',
    });

    expect(result.css).toContain('xxl-width: 1320px');
    expect(result.css).toContain('content-cap: 1140px');
    expect(result.css).toMatch(/danger-hover:\s*(?!#dc3545)[^;]+;/);
  });
});
