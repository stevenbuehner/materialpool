const listeners = new Map();

function on(eventName, listener) {
    const eventListeners = listeners.get(eventName) || new Set();
    eventListeners.add(listener);
    listeners.set(eventName, eventListeners);
}

function off(eventName, listener) {
    const eventListeners = listeners.get(eventName);
    eventListeners?.delete(listener);

    if (eventListeners?.size === 0) {
        listeners.delete(eventName);
    }
}

function emit(eventName, payload) {
    for (const listener of listeners.get(eventName) || []) {
        listener(payload);
    }
}

export const onBiblePopoverOpening = listener => on('opening', listener);
export const offBiblePopoverOpening = listener => off('opening', listener);
export const emitBiblePopoverOpening = popover => emit('opening', popover);
export const emitBiblePopoverPrevious = popover => emit('previous', popover);
export const emitBiblePopoverNext = popover => emit('next', popover);
