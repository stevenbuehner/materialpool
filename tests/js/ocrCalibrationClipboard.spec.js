import {afterEach, describe, expect, it, vi} from 'vitest';
import {copyOcrTextToClipboard} from '../../resources/js/apps/main/pages/ocrCalibrationClipboard';

const text = 'Erste Zeile\nZweite Zeile\n\nLetzte Zeile ';

function stubLegacyClipboard(result = true) {
  const textarea = {
    value: '',
    readOnly: false,
    style: {},
    focus: vi.fn(),
    select: vi.fn(),
    setSelectionRange: vi.fn(),
    remove: vi.fn(),
  };
  const previousFocus = {focus: vi.fn()};
  const document = {
    activeElement: previousFocus,
    body: {appendChild: vi.fn()},
    createElement: vi.fn(() => textarea),
    execCommand: vi.fn(() => result),
  };
  vi.stubGlobal('document', document);
  return {textarea, document, previousFocus};
}

afterEach(() => vi.unstubAllGlobals());

describe('OCR-Text kopieren', () => {
  it('übergibt den vollständigen Text an die Clipboard API', async () => {
    const writeText = vi.fn().mockResolvedValue(undefined);
    vi.stubGlobal('navigator', {clipboard: {writeText}});

    await copyOcrTextToClipboard(text);

    expect(writeText).toHaveBeenCalledExactlyOnceWith(text);
  });

  it('kopiert über ein Textfeld, wenn die Clipboard API auf HTTP fehlt', async () => {
    vi.stubGlobal('navigator', {});
    const {textarea, document, previousFocus} = stubLegacyClipboard();

    await copyOcrTextToClipboard(text);

    expect(textarea.value).toBe(text);
    expect(textarea.setSelectionRange).toHaveBeenCalledWith(0, text.length);
    expect(document.execCommand).toHaveBeenCalledExactlyOnceWith('copy');
    expect(textarea.remove).toHaveBeenCalledOnce();
    expect(previousFocus.focus).toHaveBeenCalledOnce();
  });

  it('meldet einen fehlgeschlagenen Fallback und räumt das Textfeld auf', async () => {
    vi.stubGlobal('navigator', {clipboard: {writeText: vi.fn().mockRejectedValue(new Error('Denied'))}});
    const {textarea} = stubLegacyClipboard(false);

    await expect(copyOcrTextToClipboard(text)).rejects.toThrow('Could not copy OCR text');
    expect(textarea.remove).toHaveBeenCalledOnce();
  });
});
