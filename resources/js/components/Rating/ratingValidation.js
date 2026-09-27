export function isValidMaxRating(value) {
  return typeof value === 'number' && Number.isFinite(value) && value > 0;
}
