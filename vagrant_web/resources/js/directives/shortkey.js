const modifierKeys = new Set(['shift', 'ctrl', 'meta', 'alt']);
const shortcutListeners = new WeakMap();

const namedKeys = {
    ' ': 'space',
    ArrowDown: 'arrowdown',
    ArrowLeft: 'arrowleft',
    ArrowRight: 'arrowright',
    ArrowUp: 'arrowup',
    Backspace: 'backspace',
    Delete: 'del',
    End: 'end',
    Enter: 'enter',
    Escape: 'esc',
    Home: 'home',
    Insert: 'insert',
    PageDown: 'pagedown',
    PageUp: 'pageup',
    Tab: 'tab',
};

export function encodeShortcut(keys) {
    const normalizedKeys = Array.isArray(keys) ? keys.map(key => String(key).toLowerCase()) : [];
    const modifiers = ['shift', 'ctrl', 'meta', 'alt']
        .filter(modifier => normalizedKeys.includes(modifier));
    const regularKeys = normalizedKeys.filter(key => !modifierKeys.has(key));

    return [...modifiers, ...regularKeys].join('');
}

export function encodeKeyboardEvent(event) {
    const modifiers = [
        event.shiftKey ? 'shift' : '',
        event.ctrlKey ? 'ctrl' : '',
        event.metaKey ? 'meta' : '',
        event.altKey ? 'alt' : '',
    ].filter(Boolean);
    const namedKey = namedKeys[event.key];
    const regularKey = namedKey
        || (event.key && (event.key.length === 1 || /^F\d{1,2}$/.test(event.key))
            ? event.key.toLowerCase()
            : '');

    return [...modifiers, regularKey].filter(Boolean).join('');
}

function removeListener(element) {
    const listener = shortcutListeners.get(element);

    if (!listener) {
        return;
    }

    document.removeEventListener('keydown', listener, true);
    shortcutListeners.delete(element);
}

function addListener(element, keys) {
    removeListener(element);

    const encodedShortcut = encodeShortcut(keys);
    const listener = (event) => {
        if (encodeKeyboardEvent(event) !== encodedShortcut) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        element.dispatchEvent(new CustomEvent('shortkey'));
    };

    shortcutListeners.set(element, listener);
    document.addEventListener('keydown', listener, true);
}

export const shortkeyDirective = {
    mounted(element, binding) {
        addListener(element, binding.value);
    },
    updated(element, binding) {
        if (binding.value !== binding.oldValue) {
            addListener(element, binding.value);
        }
    },
    unmounted(element) {
        removeListener(element);
    },
};

export default {
    install(app) {
        app.directive('shortkey', shortkeyDirective);
    },
};
