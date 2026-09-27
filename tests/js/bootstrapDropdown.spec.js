import {describe, expect, it} from 'vitest';
import {dropdownItemIndex} from '../../resources/js/adapters/bootstrap-dropdown';

describe('Bootstrap dropdown compatibility', () => {
    it('wraps arrow navigation in both directions', () => {
        expect(dropdownItemIndex(3, 2, 'ArrowDown')).toBe(0);
        expect(dropdownItemIndex(3, 0, 'ArrowUp')).toBe(2);
    });

    it('supports menu entry plus Home and End', () => {
        expect(dropdownItemIndex(3, -1, 'ArrowDown')).toBe(0);
        expect(dropdownItemIndex(3, -1, 'ArrowUp')).toBe(2);
        expect(dropdownItemIndex(3, 1, 'Home')).toBe(0);
        expect(dropdownItemIndex(3, 1, 'End')).toBe(2);
        expect(dropdownItemIndex(0, -1, 'ArrowDown')).toBe(-1);
    });
});
