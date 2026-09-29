export function validConfidencePercent(value) {
  if (value === '') return true;
  const number = Number(value);
  return Number.isFinite(number) && number >= 0 && number <= 100;
}

export function filterOcrCalibrationPages(pages, {quality, review, reference, minimumConfidence}) {
  const threshold = minimumConfidence === '' || !validConfidencePercent(minimumConfidence)
    ? null
    : Number(minimumConfidence) / 100;

  return pages.filter(page => {
    if (quality && page.savedQualityLabel !== quality) return false;
    if (review === 'reviewed' && !page.reviewSaved) return false;
    if (review === 'unreviewed' && page.reviewSaved) return false;
    if (reference === 'with' && !page.savedReferencePresent) return false;
    if (reference === 'without' && page.savedReferencePresent) return false;
    if (threshold !== null && (page.metrics?.mean_confidence == null
      || !Number.isFinite(Number(page.metrics.mean_confidence))
      || Number(page.metrics.mean_confidence) < threshold)) return false;
    return true;
  });
}
