export function dropdownItemIndex(itemCount, currentIndex, direction) {
    if (itemCount < 1) return -1;
    if (direction === 'Home') return 0;
    if (direction === 'End') return itemCount - 1;
    if (currentIndex < 0) return direction === 'ArrowUp' ? itemCount - 1 : 0;
    const offset = direction === 'ArrowUp' ? -1 : 1;
    return (currentIndex + offset + itemCount) % itemCount;
}
