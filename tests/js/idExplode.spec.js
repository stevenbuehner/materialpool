import { describe, expect, it } from 'vitest';
import {
    extractValueById,
    idExplode,
    overrideValueById,
    removeValueById,
    verifyStructure,
} from '../../resources/js/apps/main/store/helper/idExplode.js';

describe('idExplode helper contracts', () => {
    it('splits paths and supports a custom separator', () => {
        expect(idExplode('material.title')).toEqual(['material', 'title']);
        expect(idExplode('material/title', '/')).toEqual(['material', 'title']);
        expect(idExplode()).toEqual([]);
    });

    it('reads nested values and returns the supplied fallback', () => {
        const data = { material: { title: 'Alt', rating: 0 } };

        expect(extractValueById('material.title', data, 'Fallback')).toBe('Alt');
        expect(extractValueById('material.rating', data, 'Fallback')).toBe(0);
        expect(extractValueById('material.missing', data, 'Fallback')).toBe('Fallback');
    });

    it('writes and removes nested values in place', () => {
        const data = { material: { title: 'Alt' } };

        expect(overrideValueById('material.title', data, 'Neu')).toBe(data);
        expect(data.material.title).toBe('Neu');
        expect(removeValueById('material.title', data)).toBe(data);
        expect(data.material).not.toHaveProperty('title');
    });

    it('preserves the established structure normalization', () => {
        expect(verifyStructure(null)).toEqual({});
        expect(verifyStructure({ value: 1 })).toEqual({ value: 1 });
    });
});
