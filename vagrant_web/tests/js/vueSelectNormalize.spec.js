import {describe, expect, it}                  from 'vitest';
import {stableOptionKey, unwrapSelectOption} from '../../resources/js/adapters/vue-select-normalize';

describe('Vue-select compatibility normalization', () => {
  it('creates the same key for equivalent objects regardless of property order', () => {
    expect(stableOptionKey({title: 'Alpha', item: {type: 'k', id: 12}}))
        .toBe(stableOptionKey({item: {id: 12, type: 'k'}, title: 'Alpha'}));
  });

  it('keeps primitive values distinct and unwraps adapter options', () => {
    expect(stableOptionKey(12)).toBe('12');
    expect(stableOptionKey(null)).toBe('null');

    const original = {id: 7, title: 'Original'};
    expect(unwrapSelectOption({__materialpoolOption: original})).toBe(original);
    expect(unwrapSelectOption(original)).toBe(original);
  });
});
