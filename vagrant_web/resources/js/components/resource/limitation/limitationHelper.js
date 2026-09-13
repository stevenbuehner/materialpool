export function isResourceTypeLimitable(type) {

	switch (type) {
		case 'audio':
		case 'video':
		case 'pdf':
			return true;
		case 'image':
		case 'text':
		case 'doc':
		case 'res':
			return false;
		default:
			console.log('Unknown Resource-Type: ' + type);
			return false;
	}

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

	ranges.push({from: lastStart, to: lastEnd});

	return ranges;

}
