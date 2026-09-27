import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

const bootstrap = vi.hoisted(() => ({
    getOrCreateInstance: vi.fn(),
    hide: vi.fn(),
    show: vi.fn(),
}));

vi.mock('bootstrap', () => ({
    Modal: {
        getOrCreateInstance: bootstrap.getOrCreateInstance,
    },
}));

import {
    focusWhenModalIsShown,
    hideModal,
    showModal,
} from '../../resources/js/components/passport/modal.js';

describe('Passport modal DOM adapter', () => {
    let input;
    let modal;

    beforeEach(() => {
        input = {focus: vi.fn()};
        modal = {
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        };

        vi.stubGlobal('document', {
            querySelector: vi.fn(selector => ({
                '#modal': modal,
                '#input': input,
            })[selector] ?? null),
        });

        bootstrap.getOrCreateInstance.mockReturnValue({
            hide: bootstrap.hide,
            show: bootstrap.show,
        });
    });

    afterEach(() => {
        vi.clearAllMocks();
        vi.unstubAllGlobals();
    });

    it('controls existing Bootstrap modals', () => {
        showModal('#modal');
        hideModal('#modal');

        expect(bootstrap.getOrCreateInstance).toHaveBeenCalledTimes(2);
        expect(bootstrap.show).toHaveBeenCalledOnce();
        expect(bootstrap.hide).toHaveBeenCalledOnce();
    });

    it('focuses after opening and removes its listener during cleanup', () => {
        const cleanup = focusWhenModalIsShown('#modal', '#input');
        const shownHandler = modal.addEventListener.mock.calls[0][1];

        shownHandler();
        cleanup();

        expect(input.focus).toHaveBeenCalledOnce();
        expect(modal.removeEventListener).toHaveBeenCalledWith(
            'shown.bs.modal',
            shownHandler,
        );
    });

    it('ignores modal selectors that are not present', () => {
        expect(() => showModal('#missing')).not.toThrow();
        expect(() => hideModal('#missing')).not.toThrow();
        expect(() => focusWhenModalIsShown('#missing', '#input')()).not.toThrow();
    });
});
