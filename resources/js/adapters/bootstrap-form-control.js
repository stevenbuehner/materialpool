export function formControlClasses({plaintext = false, size = null, state = null} = {}) {
    return {
        'form-control': !plaintext,
        'form-control-plaintext': plaintext,
        [`form-control-${size}`]: Boolean(size),
        'is-valid': state === true,
        'is-invalid': state === false,
    };
}

export function debounceMilliseconds(value) {
    const parsed = Number.parseInt(value, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
}
