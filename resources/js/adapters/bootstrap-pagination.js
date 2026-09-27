export function paginationPages(current, total, limit) {
    const pageCount = Math.max(1, Number.parseInt(total, 10) || 1);
    const visibleLimit = Math.max(3, Number.parseInt(limit, 10) || 5);
    if (pageCount <= visibleLimit) {
        return Array.from({length: pageCount}, (_, index) => index + 1);
    }

    const half = Math.floor(visibleLimit / 2);
    const start = Math.min(Math.max(1, current - half), pageCount - visibleLimit + 1);
    return Array.from({length: visibleLimit}, (_, index) => start + index);
}
