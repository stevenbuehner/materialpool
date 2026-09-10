import {describe, expect, it} from 'vitest';
import {navbarClasses} from '../../resources/js/adapters/bootstrap-navbar';

describe('Bootstrap navbar compatibility', () => {
    it('maps the responsive, color and fixed-position contract', () => {
        expect(navbarClasses({fixed: 'top', toggleable: 'md', type: 'light', variant: 'light'})).toEqual([
            'navbar',
            'navbar-expand-md',
            'navbar-light',
            'bg-light',
            'fixed-top',
        ]);
    });

    it('omits classes for unused optional props', () => {
        expect(navbarClasses({fixed: null, toggleable: false, type: null, variant: null})).toEqual(['navbar']);
    });
});
