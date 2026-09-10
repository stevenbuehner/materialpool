export function normalizeSelectOption(option) {
    if (option !== null && typeof option === 'object') {
        return {
            disabled: Boolean(option.disabled),
            text: option.text ?? option.value,
            value: option.value,
        };
    }

    return {disabled: false, text: option, value: option};
}
