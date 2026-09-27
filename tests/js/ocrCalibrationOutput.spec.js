import {describe, expect, it} from 'vitest';
import {ocrOutput} from '../../resources/js/apps/main/pages/ocrCalibrationOutput';

const translate = key => ({
  'pool.ocr-calibration-no-text-recognized': 'Kein Text erkannt.',
  'pool.ocr-calibration-text-unavailable': 'OCR-Text noch nicht verfügbar.',
})[key];

describe('OCR-Kalibrierungsanzeige', () => {
  it('zeigt den unveränderten OCR-Text, wenn Text erkannt wurde', () => {
    expect(ocrOutput({status: 'processed', ocr_text: 'Erkannter Text\n'}, translate)).toBe('Erkannter Text\n');
  });

  it('stellt einen leeren OCR-Text nicht als Status oder erkannten Text dar', () => {
    expect(ocrOutput({status: 'processed', ocr_text: ''}, translate)).toBe('Kein Text erkannt.');
    expect(ocrOutput({status: 'processed', ocr_text: ' \n'}, translate)).toBe('Kein Text erkannt.');
  });

  it('kennzeichnet ausstehende oder fehlgeschlagene Ergebnisse separat', () => {
    expect(ocrOutput({status: 'pending', ocr_text: null}, translate)).toBe('OCR-Text noch nicht verfügbar.');
    expect(ocrOutput({status: 'failed', ocr_text: null}, translate)).toBe('OCR-Text noch nicht verfügbar.');
  });
});
