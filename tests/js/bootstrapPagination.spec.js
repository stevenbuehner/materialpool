import {describe, expect, it} from 'vitest';
import {paginationPages} from '../../resources/js/adapters/bootstrap-pagination';

describe('Bootstrap pagination compatibility', () => {
    it('shows all pages below the configured limit', () => {
        expect(paginationPages(1, 4, 10)).toEqual([1, 2, 3, 4]);
    });

    it('moves a bounded window around the current page', () => {
        expect(paginationPages(1, 20, 5)).toEqual([1, 2, 3, 4, 5]);
        expect(paginationPages(10, 20, 5)).toEqual([8, 9, 10, 11, 12]);
        expect(paginationPages(20, 20, 5)).toEqual([16, 17, 18, 19, 20]);
    });
});
