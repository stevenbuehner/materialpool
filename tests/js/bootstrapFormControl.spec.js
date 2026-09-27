import {describe, expect, it} from 'vitest';
import {debounceMilliseconds, formControlClasses} from '../../resources/js/adapters/bootstrap-form-control';

describe('Bootstrap form control compatibility', () => {
    it('maps BootstrapVue state and size to Bootstrap 5 form-control classes', () => {
        expect(formControlClasses({size: 'sm', state: false})).toEqual({
            'form-control': true,
            'form-control-plaintext': false,
            'form-control-sm': true,
            'is-valid': false,
            'is-invalid': true,
        });
    });

    it('keeps plaintext controls out of the standard form-control class', () => {
        expect(formControlClasses({plaintext: true})).toMatchObject({
            'form-control': false,
            'form-control-plaintext': true,
        });
    });

    it('normalizes invalid and disabled debounce values to immediate input', () => {
        expect(debounceMilliseconds('500')).toBe(500);
        expect(debounceMilliseconds(0)).toBe(0);
        expect(debounceMilliseconds('invalid')).toBe(0);
    });
});
