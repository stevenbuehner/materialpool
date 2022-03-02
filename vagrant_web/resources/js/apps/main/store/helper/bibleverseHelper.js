export function getRangeId(from, to, translation) {
	translation = translation || '';
	return parseInt(from) + '-' + parseInt(to) + translation;
}