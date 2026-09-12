export function limitedPreviewPages(previewablePages, maxPagesToDisplay) {
  return previewablePages.slice(0, Math.min(maxPagesToDisplay, previewablePages.length));
}
