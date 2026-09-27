import { describe, expect, it } from 'vitest';
import { uniqueArray } from '../../resources/js/helper/ArrayHelper.js';

describe('uniqueArray', () => {
    it('removes duplicate primitive values and returns the same array', () => {
        const values = ['a', 'b', 'a', 'c', 'b'];

        expect(uniqueArray(values)).toBe(values);
        expect(values).toEqual(['a', 'b', 'c']);
    });
});
