export function getTagLabelFromKeywordObject(value) {
	if (typeof value === 'object') {
		if (!value.hasOwnProperty('title')) {
			return console.warn(
				`[vue-select warn]: Label key "option.title" does not` +
				` exist in options object ${JSON.stringify(value)}.\n` +
				'http://sagalbot.github.io/vue-select/#ex-labels'
			)
		} else {
			return value.title;
		}

	} else {
		return value;
	}
};

export function getOptionKeyFromKeywordObject(el){
	return el.id;
}
