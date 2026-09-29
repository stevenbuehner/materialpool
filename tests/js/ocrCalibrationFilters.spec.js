import {describe, expect, it} from 'vitest';
import {filterOcrCalibrationPages, validConfidencePercent} from '../../resources/js/apps/main/pages/ocrCalibrationFilters';

const pages = [
  {id: 1, savedQualityLabel: 'usable', reviewSaved: true, savedReferencePresent: true, metrics: {mean_confidence: 0.9}},
  {id: 2, savedQualityLabel: 'unusable', reviewSaved: true, savedReferencePresent: false, metrics: {mean_confidence: 0.8}},
  {id: 3, savedQualityLabel: null, reviewSaved: false, savedReferencePresent: false, metrics: {mean_confidence: 0.5}},
  {id: 4, savedQualityLabel: null, reviewSaved: false, savedReferencePresent: false, metrics: null},
];
const all = {quality: '', review: '', reference: '', minimumConfidence: ''};
const ids = filtered => filtered.map(page => page.id);

describe('OCR-Kalibrierungsfilter', () => {
  it('kombiniert Qualitätsbewertung, Bewertungsstand, Referenztext und inklusive Mindestkonfidenz', () => {
    expect(ids(filterOcrCalibrationPages(pages, all))).toEqual([1, 2, 3, 4]);
    expect(ids(filterOcrCalibrationPages(pages, {...all, quality: 'usable', review: 'reviewed', reference: 'with', minimumConfidence: '90'}))).toEqual([1]);
    expect(ids(filterOcrCalibrationPages(pages, {...all, review: 'unreviewed', reference: 'without', minimumConfidence: '50'}))).toEqual([3]);
    expect(ids(filterOcrCalibrationPages(pages, {...all, reference: 'with'}))).toEqual([1]);
  });

  it('schließt Seiten ohne Konfidenz bei gesetztem Mindestwert aus und prüft die Prozentgrenzen', () => {
    expect(ids(filterOcrCalibrationPages(pages, {...all, minimumConfidence: '0'}))).toEqual([1, 2, 3]);
    expect(validConfidencePercent('')).toBe(true);
    expect(validConfidencePercent('100')).toBe(true);
    expect(validConfidencePercent('-1')).toBe(false);
    expect(validConfidencePercent('101')).toBe(false);
  });

  it('filtert nach dem gespeicherten Stand und ignoriert ungespeicherte Formulareingaben', () => {
    const edited = {...pages[0], quality_label: 'unusable', reference_text: ''};
    expect(ids(filterOcrCalibrationPages([edited], {...all, quality: 'usable', reference: 'with'}))).toEqual([1]);
  });
});
