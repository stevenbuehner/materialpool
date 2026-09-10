import {describe, expect, it} from 'vitest';
import {modalDialogClasses} from '../../resources/js/adapters/bootstrap-modal';

describe('Bootstrap modal compatibility', () => {
    it('maps size, position, scrolling and custom dialog classes', () => {
        expect(modalDialogClasses({
            centered: true,
            dialogClass: 'custom-dialog',
            scrollable: true,
            size: 'lg',
        })).toEqual([
            'modal-dialog',
            'modal-lg',
            'modal-dialog-centered',
            'modal-dialog-scrollable',
            'custom-dialog',
        ]);
    });

    it('omits unused optional classes', () => {
        expect(modalDialogClasses({centered: false, dialogClass: null, scrollable: false, size: null}))
            .toEqual(['modal-dialog']);
    });
});
