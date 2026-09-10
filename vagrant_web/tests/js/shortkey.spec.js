import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import {
    encodeKeyboardEvent,
    encodeShortcut,
    shortkeyDirective,
} from '../../resources/js/directives/shortkey.js';

describe('shortkey directive', () => {
    let listeners;

    beforeEach(() => {
        listeners = new Map();
        vi.stubGlobal('document', {
            addEventListener: vi.fn((type, listener) => listeners.set(type, listener)),
            removeEventListener: vi.fn((type, listener) => {
                if (listeners.get(type) === listener) {
                    listeners.delete(type);
                }
            }),
        });
        vi.stubGlobal('CustomEvent', class CustomEvent {
            constructor(type) {
                this.type = type;
            }
        });
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('uses the same exact modifier and key encoding as the existing ctrl+n binding', () => {
        expect(encodeShortcut(['ctrl', 'n'])).toBe('ctrln');
        expect(encodeKeyboardEvent({ key: 'N', ctrlKey: true })).toBe('ctrln');
        expect(encodeKeyboardEvent({ key: 'N', ctrlKey: true, shiftKey: true })).toBe('shiftctrln');
    });

    it('dispatches shortkey and suppresses the browser action only for a match', () => {
        const element = { dispatchEvent: vi.fn() };
        const preventDefault = vi.fn();
        const stopPropagation = vi.fn();

        shortkeyDirective.mounted(element, { value: ['ctrl', 'n'] });
        listeners.get('keydown')({
            key: 'n',
            ctrlKey: true,
            preventDefault,
            stopPropagation,
        });

        expect(preventDefault).toHaveBeenCalledOnce();
        expect(stopPropagation).toHaveBeenCalledOnce();
        expect(element.dispatchEvent).toHaveBeenCalledWith(expect.objectContaining({ type: 'shortkey' }));

        shortkeyDirective.unmounted(element);
        expect(listeners.has('keydown')).toBe(false);
    });

    it('leaves unrelated keyboard events untouched', () => {
        const element = { dispatchEvent: vi.fn() };
        const preventDefault = vi.fn();

        shortkeyDirective.mounted(element, { value: ['ctrl', 'n'] });
        listeners.get('keydown')({ key: 'n', preventDefault });

        expect(preventDefault).not.toHaveBeenCalled();
        expect(element.dispatchEvent).not.toHaveBeenCalled();
    });
});
