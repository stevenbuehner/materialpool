import { describe, expect, it, vi } from 'vitest';

import {
    emitBiblePopoverOpening,
    offBiblePopoverOpening,
    onBiblePopoverOpening,
} from '../../resources/js/components/bible-popover/bible-popover-events.js';

describe('bible popover events', () => {
    it('notifies active opening listeners and supports explicit cleanup', () => {
        const listener = vi.fn();
        const popover = { id: 42 };

        onBiblePopoverOpening(listener);
        emitBiblePopoverOpening(popover);
        offBiblePopoverOpening(listener);
        emitBiblePopoverOpening({ id: 43 });

        expect(listener).toHaveBeenCalledTimes(1);
        expect(listener).toHaveBeenCalledWith(popover);
    });
});
