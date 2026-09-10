import { describe, expect, it } from 'vitest';

import { getBibleverseTokenizer } from '../../resources/js/components/markdown/bibleverseRenderer.js';

describe('bibleverse markdown renderer', () => {
    it('emits inert data attributes instead of Vue template bindings', () => {
        const extension = getBibleverseTokenizer(true);
        const html = extension.renderer({
            bibleverse: 'Joh 3,16',
            doAutoload: true,
        });

        expect(html).toContain('data-text="Joh 3,16"');
        expect(html).toContain('data-load-contents="true"');
        expect(html).not.toContain(':text=');
        expect(html).not.toContain(':load-contents=');
    });
});
