export function navbarClasses({fixed, toggleable, type, variant}) {
    return [
        'navbar',
        toggleable ? `navbar-expand-${toggleable === true ? 'sm' : toggleable}` : null,
        type ? `navbar-${type}` : null,
        variant ? `bg-${variant}` : null,
        fixed ? `fixed-${fixed}` : null,
    ].filter(Boolean);
}
