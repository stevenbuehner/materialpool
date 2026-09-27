export function modalDialogClasses({centered, dialogClass, scrollable, size}) {
    return [
        'modal-dialog',
        size ? `modal-${size}` : null,
        centered ? 'modal-dialog-centered' : null,
        scrollable ? 'modal-dialog-scrollable' : null,
        dialogClass,
    ].filter(Boolean);
}
