import keyIcon    from '../../../icons/keyword/tag.svg';
import personIcon from '../../../icons/keyword/person.svg';
import placeIcon  from '../../../icons/keyword/place.svg';
import langIcon   from 'svg-icon/dist/svg/material/language.svg';
import langDeIcon from '../../../icons/keyword/lang-DE.svg';
import langEnIcon from '../../../icons/keyword/lang-EN.svg';
import langFrIcon from '../../../icons/keyword/lang-FR.svg';

import bibleIcon from '../../../icons/bibleverse/bible.svg';
import ayceIcon  from '../../../icons/keyword/ayce.svg';


export {
	keyIcon,
	personIcon,
	placeIcon,
	langDeIcon,
	langEnIcon,
	langFrIcon,
	bibleIcon,
	ayceIcon,
	langIcon
};

export const keywordTypes = [
	'key', 'lang', 'place', 'person'
];

export function iconName(keyword) {
	const type = keyword.type || 'unknown';

	if (keywordTypes.includes(type)) {
		return type + '-icon';
	} else {
		console.error('Could not find an icon for type: ' + type, keyword);
		return 'ayce-icon';
	}

}

export function preloadedIcon(icon) {

	switch (icon) {
		case '/img/icons/bible.svg':
			return bibleIcon;
		case '/img/icons/ayce.svg':
			return ayceIcon;
		case null:
		case '/img/icons/tag.svg':
			return keyIcon;
		case '/img/icons/place.svg':
			return placeIcon;
		case '/img/icons/person.svg':
			return personIcon;
	}

	return false;
}
