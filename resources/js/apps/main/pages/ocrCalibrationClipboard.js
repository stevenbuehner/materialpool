export async function copyOcrTextToClipboard(text) {
  if (navigator.clipboard?.writeText) {
    try {
      await navigator.clipboard.writeText(text);
      return;
    } catch {
      // In local HTTP environments the browser may reject the Clipboard API.
    }
  }

  const textarea = document.createElement('textarea');
  const previousFocus = document.activeElement;
  textarea.value = text;
  textarea.readOnly = true;
  textarea.style.position = 'fixed';
  textarea.style.left = '-9999px';
  textarea.style.top = '0';
  document.body.appendChild(textarea);

  try {
    textarea.focus();
    textarea.select();
    textarea.setSelectionRange(0, textarea.value.length);
    if (!document.execCommand('copy')) {
      throw new Error('Could not copy OCR text');
    }
  } finally {
    textarea.remove();
    previousFocus?.focus?.();
  }
}
