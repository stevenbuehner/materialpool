import {describe, expect, it} from 'vitest';
import {formGroupColumnClasses} from '../../resources/js/adapters/bootstrap-form-group';
import {normalizeSelectOption} from '../../resources/js/adapters/bootstrap-form-select';

describe('Bootstrap form layout compatibility', () => {
    it('maps responsive label columns to Bootstrap 4 classes', () => {
        expect(formGroupColumnClasses({labelCols: 3, labelColsMd: 3, labelColsLg: 1}, 'label'))
            .toEqual(['col-3', 'col-md-3', 'col-lg-1']);
    });

    it('does not infer a horizontal layout from obsolete horizontal attributes', () => {
        expect(formGroupColumnClasses({horizontal: true, breakpoint: 'md'}, 'label')).toEqual([]);
    });

    it('normalizes primitive and object select options without changing values', () => {
        expect(normalizeSelectOption(20)).toEqual({disabled: false, text: 20, value: 20});
        expect(normalizeSelectOption({value: 'id', text: 'ID', disabled: true})).toEqual({
            disabled: true,
            text: 'ID',
            value: 'id',
        });
    });
});
