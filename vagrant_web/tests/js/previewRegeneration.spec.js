import {describe, expect, it} from 'vitest';
import {
    isPreviewRegenerationActive,
    regeneratePreview,
} from '../../resources/js/components/resource/preview-regeneration.js';

describe('preview regeneration', () => {
    it('blocks a second regeneration for the same resource until the first finishes', async () => {
        let finish;
        const first = regeneratePreview(42, () => new Promise(resolve => {
            finish = resolve;
        }));

        expect(isPreviewRegenerationActive(42)).toBe(true);
        await expect(regeneratePreview(42, () => Promise.resolve())).resolves.toBe(false);

        finish();
        await expect(first).resolves.toBe(true);
        expect(isPreviewRegenerationActive(42)).toBe(false);
    });

    it('unblocks a resource when regeneration fails', async () => {
        await expect(regeneratePreview(43, () => Promise.reject(new Error('failed')))).rejects.toThrow('failed');

        expect(isPreviewRegenerationActive(43)).toBe(false);
    });
});
