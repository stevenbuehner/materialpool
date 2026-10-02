import {describe, expect, it, vi} from 'vitest';
import {searchQueryStringToSearchQueryArray} from '../../resources/js/components/search/searchHelper';

vi.mock('../../resources/js/apps/main/stores/keywords', () => ({useKeywordsStore: vi.fn()}));

describe('search URL parsing', () => {
    it('passes Bible verse ranges with leading zeros as numeric search values', () => {
        expect(searchQueryStringToSearchQueryArray('1b041012028-041012031,1b46010031-46010031')).toEqual({
            1: [
                {type: 'b', from: 41012028, to: 41012031},
                {type: 'b', from: 46010031, to: 46010031},
            ],
        });
    });

    it('ignores a Bible verse range without numeric bounds', () => {
        expect(searchQueryStringToSearchQueryArray('1babc-def')).toEqual({});
    });
});
