import {describe, expect, it} from 'vitest';
import {
    adjacentEnabledTabIndex,
    enabledTabIndex,
    normalizeTabIndex,
} from '../../resources/js/adapters/bootstrap-tabs';

describe('Bootstrap tabs compatibility', () => {
    it('normalizes invalid and negative indexes', () => {
        expect(normalizeTabIndex(-2)).toBe(0);
        expect(normalizeTabIndex('2')).toBe(2);
        expect(normalizeTabIndex('invalid')).toBe(0);
    });

    it('selects an enabled tab at or after the requested index', () => {
        const tabs = [{disabled: false}, {disabled: true}, {disabled: false}];
        expect(enabledTabIndex(tabs, 1)).toBe(2);
        expect(enabledTabIndex(tabs, 9)).toBe(0);
        expect(enabledTabIndex([{disabled: true}], 0)).toBe(-1);
    });

    it('wraps keyboard navigation and skips disabled tabs', () => {
        const tabs = [{disabled: false}, {disabled: true}, {disabled: false}];
        expect(adjacentEnabledTabIndex(tabs, 0, 'next')).toBe(2);
        expect(adjacentEnabledTabIndex(tabs, 2, 'next')).toBe(0);
        expect(adjacentEnabledTabIndex(tabs, 0, 'previous')).toBe(2);
        expect(adjacentEnabledTabIndex(tabs, 2, 'first')).toBe(0);
        expect(adjacentEnabledTabIndex(tabs, 0, 'last')).toBe(2);
    });
});
