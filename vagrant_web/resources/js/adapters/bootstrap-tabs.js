export function normalizeTabIndex(value) {
    return Math.max(0, Number.parseInt(value, 10) || 0);
}

export function enabledTabIndex(tabs, requestedIndex) {
    const normalized = normalizeTabIndex(requestedIndex);
    const following = tabs.findIndex((tab, index) => index >= normalized && !tab.disabled);
    return following === -1 ? tabs.findIndex(tab => !tab.disabled) : following;
}

export function adjacentEnabledTabIndex(tabs, currentIndex, direction) {
    const enabledIndexes = tabs.reduce((indexes, tab, index) => {
        if (!tab.disabled) indexes.push(index);
        return indexes;
    }, []);

    if (enabledIndexes.length === 0) return -1;
    if (direction === 'first') return enabledIndexes[0];
    if (direction === 'last') return enabledIndexes[enabledIndexes.length - 1];

    const position = enabledIndexes.indexOf(currentIndex);
    const offset = direction === 'previous' ? -1 : 1;
    return enabledIndexes[(position + offset + enabledIndexes.length) % enabledIndexes.length];
}
