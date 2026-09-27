function normalizeForStringify(value) {
  if (Array.isArray(value)) {
    return value.map(normalizeForStringify);
  }

  if (value && typeof value === 'object') {
    return Object.keys(value)
        .sort()
        .reduce((normalized, key) => {
          normalized[key] = normalizeForStringify(value[key]);
          return normalized;
        }, {});
  }

  return value;
}

export function stableOptionKey(value) {
  if (value === null || typeof value !== 'object') {
    return String(value);
  }

  return JSON.stringify(normalizeForStringify(value));
}

export function unwrapSelectOption(option) {
  return option && option.__materialpoolOption !== undefined
      ? option.__materialpoolOption
      : option;
}
