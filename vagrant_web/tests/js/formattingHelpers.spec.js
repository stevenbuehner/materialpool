import { afterAll, beforeAll, describe, expect, it, vi } from 'vitest';

import { trim } from '../../resources/js/filters/truncate-filter.mixin.js';
import { readableBytes } from '../../resources/js/helper/filesize.mixin.js';

let dayjs;
let format;

beforeAll(async () => {
    vi.stubGlobal('document', { documentElement: { lang: 'de' } });
    ({ dayjs, format } = await import('../../resources/js/helper/datetime.mixin.js'));
});

afterAll(() => {
    vi.unstubAllGlobals();
});

describe('formatting helper contracts', () => {
    it('keeps the established word-aware truncation', () => {
        expect(trim('Kurzer Text', 30)).toBe('Kurzer Text');
        expect(trim('Ein deutlich längerer Text', 15)).toBe('Ein...');
        expect(trim('ohneleerzeichen', 8)).toBe('ohnel...');
    });

    it('formats dates through the existing dayjs setup', () => {
        expect(format(dayjs('2024-02-03T04:05:00'), 'YYYY-MM-DD HH:mm')).toBe('2024-02-03 04:05');
    });

    it('formats decimal byte units and preserves signs', () => {
        expect(readableBytes(0)).toBe('0 B');
        expect(readableBytes(1500)).toBe('1.5 KB');
        expect(readableBytes(-1500)).toBe('-1.5 KB');
    });
});
