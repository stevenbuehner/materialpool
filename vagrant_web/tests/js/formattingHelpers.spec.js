import { afterAll, afterEach, beforeAll, describe, expect, it, vi } from 'vitest';

import { trim } from '../../resources/js/filters/truncate-filter.mixin.js';
import { displayFilesize, readableBytes } from '../../resources/js/helper/filesize.mixin.js';

let dayjs;
let format;
let fromNow;
let recentOrFormat;
let toNow;

beforeAll(async () => {
    vi.stubGlobal('document', { documentElement: { lang: 'de' } });
    ({ dayjs, format, fromNow, recentOrFormat, toNow } = await import('../../resources/js/helper/datetime.mixin.js'));
});

afterAll(() => {
    vi.unstubAllGlobals();
});

afterEach(() => {
    vi.useRealTimers();
});

describe('formatting helper contracts', () => {
    it('keeps the established word-aware truncation', () => {
        expect(trim('Kurzer Text', 30)).toBe('Kurzer Text');
        expect(trim('Ein deutlich längerer Text', 15)).toBe('Ein...');
        expect(trim('ohneleerzeichen', 8)).toBe('ohnel...');
    });

    it('formats dates through the existing dayjs setup', () => {
        expect(format(dayjs('2024-02-03T04:05:00'), 'YYYY-MM-DD HH:mm')).toBe('2024-02-03 04:05');
        expect(format(dayjs('2024-02-03T04:05:00'), 'L')).toBe('03.02.2024');
    });

    it('keeps valid and invalid date detection available', () => {
        expect(dayjs('2024-02-03T04:05:00').isValid()).toBe(true);
        expect(dayjs('kein-datum').isValid()).toBe(false);
    });

    it('keeps localized relative date output', () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2024-02-03T12:00:00Z'));

        expect(fromNow(dayjs('2024-02-03T11:00:00Z'))).toBe('vor einer Stunde');
        expect(toNow(dayjs('2024-02-03T11:00:00Z'))).toBe('in einer Stunde');
        expect(recentOrFormat(dayjs('2024-01-01T12:00:00Z'))).toContain('Januar');

    });

    it('formats decimal byte units and preserves signs', () => {
        expect(readableBytes(0)).toBe('0 B');
        expect(readableBytes(1500)).toBe('1.5 KB');
        expect(readableBytes(-1500)).toBe('-1.5 KB');
    });

    it('distinguishes pending filesizes from valid empty resources', () => {
        expect(displayFilesize(null, 'noch nicht ausgerechnet')).toBe('noch nicht ausgerechnet');
        expect(displayFilesize(undefined, 'noch nicht ausgerechnet')).toBe('noch nicht ausgerechnet');
        expect(displayFilesize(0, 'noch nicht ausgerechnet')).toBe('0 B');
    });
});
