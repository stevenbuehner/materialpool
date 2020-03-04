export function isResourceTypeLimitable(type) {

	let isLimitable = false;

	switch (type) {
		case 'audio':
		case 'video':
		case 'pdf':
			isLimitable = true;
			break;
		case 'image':
		case 'text':
		case 'doc':
		case 'res':
			isLimitable = false;
			break;
		default:
			console.log('Unknown Resource-Type: ' + type);
			isLimitable = false;
	}

	return isLimitable;

}

export function getLimitationRangeFromPages(pages) {

	const orderedPages = pages.slice().sort(function (a, b) {
		return a > b;
	});
	let ranges         = [];
	let lastStart, lastEnd;

	if (orderedPages.length === 0) {
		return [];
	}

	lastStart = lastEnd = orderedPages.shift();

	for (let i in orderedPages) {

		const value = orderedPages[i];

		if (lastEnd + 1 === value) {
			lastEnd = value;
		} else {
			ranges.push({from: lastStart, to: lastEnd});
			lastStart = lastEnd = value;
		}
	}

	if (orderedPages.length === 0) {
		ranges.push({from: lastStart, to: lastEnd});
	}

	return ranges;

}
