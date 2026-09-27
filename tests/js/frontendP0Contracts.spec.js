import {afterAll, beforeAll, describe, expect, it, vi} from 'vitest';

import {isValidMaxRating} from '../../resources/js/components/Rating/ratingValidation';
import {fillPercentage, ratingFromPointer} from '../../resources/js/components/Rating/ratingMath';
import {limitedPreviewPages} from '../../resources/js/components/resource/show/pdfPreviewPages';

let displayedUsages;
let isValidUsedBy;
let userCanManageMaterialUsage;
let userCanManageOwnOrAll;

beforeAll(async () => {
  vi.stubGlobal('document', {documentElement: {lang: 'de'}});
  ({displayedUsages, isValidUsedBy} = await import(
    '../../resources/js/components/sidebar-fields/usage/usageHelpers'
  ));
  ({userCanManageMaterialUsage, userCanManageOwnOrAll} = await import(
    '../../resources/js/apps/main/authorization'
  ));
});

afterAll(() => {
  vi.unstubAllGlobals();
});

describe('frontend P0 contracts', () => {
  it('accepts only finite positive maximum ratings', () => {
    expect(isValidMaxRating(20)).toBe(true);
    expect(isValidMaxRating(0)).toBe(false);
    expect(isValidMaxRating(Number.POSITIVE_INFINITY)).toBe(false);
    expect(isValidMaxRating('20')).toBe(false);
  });

  it('maps five visual stars to the persisted zero-to-twenty rating scale', () => {
    expect(fillPercentage(10, 0, 5, 20)).toBe(100);
    expect(fillPercentage(10, 1, 5, 20)).toBe(100);
    expect(fillPercentage(10, 2, 5, 20)).toBe(50);
    expect(fillPercentage(10, 3, 5, 20)).toBe(0);
    expect(ratingFromPointer(0.5, 2, 5, 20, 1)).toBe(10);
  });

  it('accepts nullable users with their own id property', () => {
    expect(isValidUsedBy(null)).toBe(true);
    expect(isValidUsedBy({id: 7})).toBe(true);
    expect(isValidUsedBy({name: 'No ID'})).toBe(false);
    expect(isValidUsedBy('invalid')).toBe(false);
    expect(isValidUsedBy(Object.create({id: 7}))).toBe(false);
  });

  it('selects recent usages without mutating the source order', () => {
    const usages = [
      {id: 2, datetime: '2024-02-02T00:00:00Z'},
      {id: 1, datetime: '2024-01-01T00:00:00Z'},
      {id: 3, datetime: '2024-03-03T00:00:00Z'},
    ];

    expect(displayedUsages(usages, 2, null).map(usage => usage.id)).toEqual([2, 3]);
    expect(usages.map(usage => usage.id)).toEqual([2, 1, 3]);
  });

  it('keeps the active usage visible within the display limit', () => {
    const usages = [
      {id: 1, datetime: '2024-01-01T00:00:00Z'},
      {id: 2, datetime: '2024-02-02T00:00:00Z'},
      {id: 3, datetime: '2024-03-03T00:00:00Z'},
    ];

    expect(displayedUsages(usages, 2, 1).map(usage => usage.id)).toEqual([1, 3]);
  });

  it('matches own-or-all permissions to the record creator', () => {
    const ownOnly = {id: 10, permissions: ['resources.delete-own']};
    const all = {id: 10, permissions: ['resources.delete-all']};

    expect(userCanManageOwnOrAll(ownOnly, {created_by: 10}, 'resources.delete-own', 'resources.delete-all')).toBe(true);
    expect(userCanManageOwnOrAll(ownOnly, {created_by: 11}, 'resources.delete-own', 'resources.delete-all')).toBe(false);
    expect(userCanManageOwnOrAll(all, {created_by: 11}, 'resources.delete-own', 'resources.delete-all')).toBe(true);
  });

  it('allows usage changes only for its creator, the material creator, or an administrator', () => {
    const usage = {created_by: 21};
    const material = {created_by: 22};

    expect(userCanManageMaterialUsage({id: 21}, usage, material)).toBe(true);
    expect(userCanManageMaterialUsage({id: 22}, usage, material)).toBe(true);
    expect(userCanManageMaterialUsage({id: 23}, usage, material)).toBe(false);
    expect(userCanManageMaterialUsage({id: 23, is_admin: true}, usage, material)).toBe(true);
  });

  it('limits PDF pages without consuming the computed source array', () => {
    const pages = [1, 2, 3, 4];

    expect(limitedPreviewPages(pages, 2)).toEqual([1, 2]);
    expect(pages).toEqual([1, 2, 3, 4]);
  });
});
