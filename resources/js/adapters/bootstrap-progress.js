export function progressPercentage(value, max = 100) {
    const numericMax = Number(max);
    const numericValue = Number(value);
    if (!Number.isFinite(numericMax) || numericMax <= 0 || !Number.isFinite(numericValue)) {
        return 0;
    }
    return Math.min(100, Math.max(0, numericValue / numericMax * 100));
}
