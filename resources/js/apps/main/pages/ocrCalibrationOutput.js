export function ocrOutput(page, translate) {
  if (typeof page.ocr_text === 'string' && page.ocr_text.trim() !== '') {
    return page.ocr_text;
  }

  return translate(page.status === 'processed'
    ? 'pool.ocr-calibration-no-text-recognized'
    : 'pool.ocr-calibration-text-unavailable');
}
