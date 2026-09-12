import {moment} from '../../../apps/main/localisation';

export function isValidUsedBy(value) {
  return value === null
    || (typeof value === 'object'
      && value !== null
      && Object.prototype.hasOwnProperty.call(value, 'id'));
}

function compareUsageDates(first, second) {
  return moment(first.datetime).unix() - moment(second.datetime).unix();
}

export function displayedUsages(usages, displayMax, currentActiveUsageId) {
  const orderedUsages = [...usages].sort(compareUsageDates);

  if (orderedUsages.length <= displayMax) {
    return orderedUsages;
  }

  const result = [];
  const selectedUsage = currentActiveUsageId === null
    ? null
    : orderedUsages.find(usage => usage.id === currentActiveUsageId) ?? null;

  if (selectedUsage !== null) {
    result.push(selectedUsage);
  }

  for (let index = orderedUsages.length - 1;
       index >= 0 && result.length < displayMax;
       index--) {
    const usage = orderedUsages[index];

    if (usage.id !== selectedUsage?.id) {
      result.push(usage);
    }
  }

  return result.sort(compareUsageDates);
}
