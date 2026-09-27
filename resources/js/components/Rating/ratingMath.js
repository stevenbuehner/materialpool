export function normalizeRating(value, maxRating) {
    const numericValue = Number(value);

    if (!Number.isFinite(numericValue)) {
        return 0;
    }

    return Math.max(0, Math.min(maxRating, numericValue));
}

export function roundToIncrement(value, increment, maxRating) {
    return normalizeRating(Math.round(value / increment) * increment, maxRating);
}

export function ratingFromPointer(position, starIndex, starCount, maxRating, increment, rtl = false) {
    const relativePosition = rtl ? 1 - position : position;
    const rawRating = ((starIndex + relativePosition) / starCount) * maxRating;

    return Math.max(increment, roundToIncrement(rawRating, increment, maxRating));
}

export function fillPercentage(rating, starIndex, starCount, maxRating) {
    const relativeRating = (normalizeRating(rating, maxRating) / maxRating) * starCount;

    return Math.max(0, Math.min(100, (relativeRating - starIndex) * 100));
}
