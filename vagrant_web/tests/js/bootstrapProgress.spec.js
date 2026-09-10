import {describe, expect, it} from 'vitest';
import {progressPercentage} from '../../resources/js/adapters/bootstrap-progress';

describe('Bootstrap progress compatibility', () => {
    it('calculates and clamps percentages', () => {
        expect(progressPercentage(25, 50)).toBe(50);
        expect(progressPercentage(75, 50)).toBe(100);
        expect(progressPercentage(-5, 50)).toBe(0);
    });

    it('handles invalid maximum values defensively', () => {
        expect(progressPercentage(1, 0)).toBe(0);
        expect(progressPercentage('value', 100)).toBe(0);
    });
});
