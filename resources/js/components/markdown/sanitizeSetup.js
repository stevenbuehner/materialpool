import DOMPurify from "dompurify";

const sanitizeOptions = {
	ADD_TAGS: ['bibleverse-inline-popover-txt'],
	ADD_ATTR: ['data-text', 'data-load-contents']
};

export function sanitizeTextMarkup(htmlDirty) {
	return DOMPurify.sanitize(htmlDirty, sanitizeOptions);
}
