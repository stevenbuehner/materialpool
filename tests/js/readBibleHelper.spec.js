import {describe, expect, it} from 'vitest';
import {BibleVerse} from '../../resources/js/helper/BibleverseHelper';
import {fromRangeArrayToString} from '../../resources/js/apps/main/pages/readBibleHelper';

describe('readBibleHelper', () => {
    it('serializes BibleVerse instances and plain ranges', () => {
        expect(fromRangeArrayToString([
            new BibleVerse(1001001, 1001003),
            {from: 2002001, to: 2002002, bibleId: 'LUT'},
            {from: 3003001, to: 3003001},
        ])).toBe('001001001-001001003,2002001-2002002-LUT,3003001-3003001');
    });
});
