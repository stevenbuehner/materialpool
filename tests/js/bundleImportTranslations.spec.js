import {describe, expect, it} from 'vitest';
import Lang from 'lang.js';
import translations from '../../resources/js/lang-js-translation.json';

describe('Bundle-Import-Übersetzungen', () => {
    it('ersetzt die Zahlen der Import-Zusammenfassung', () => {
        const lang = new Lang({messages: translations, locale: 'de', fallback: 'en'});

        expect(lang.trans('pool.bundle-import-summary-materials', {successful: 2, skipped: 1, failed: 3}))
            .toBe('Materialien: 2 verarbeitet, 1 übersprungen, 3 fehlgeschlagen.');
        expect(lang.trans('pool.bundle-import-summary-removed-resources', {successful: 4, skipped: 0, failed: 1}))
            .toBe('Resources: 4 entfernt, 0 übersprungen, 1 fehlgeschlagen.');
    });
});
