const BREAKPOINTS = ['', 'sm', 'md', 'lg', 'xl'];

function columnClass(breakpoint, value) {
    if (value === true || value === '') {
        return breakpoint ? `col-${breakpoint}` : 'col';
    }

    return breakpoint ? `col-${breakpoint}-${value}` : `col-${value}`;
}

export function formGroupColumnClasses(props, prefix) {
    return BREAKPOINTS.flatMap(breakpoint => {
        const suffix = breakpoint ? `${breakpoint[0].toUpperCase()}${breakpoint.slice(1)}` : '';
        const value = props[`${prefix}Cols${suffix}`];

        return value === null || value === undefined || value === false || value === 0 || value === '0'
            ? []
            : [columnClass(breakpoint, value)];
    });
}
