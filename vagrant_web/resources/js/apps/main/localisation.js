// @see https://github.com/martinlindhe/laravel-vue-i18n-generator
import translations from './../../lang-js-translation.js';
import Moment from 'moment';
import 'moment/locale/de';

// or however you determine your current app locale
export const lang = document.documentElement.lang.substr(0, 2);

// Setup default Moment-Locale
Moment.locale(lang);

export const vueLangConfig = {
	messages: translations,
	locale: 'de',
	fallback: 'en'
};

export const localisation = {
	de: {
		dateDisplayFormat : 'DD.MM.YYYY'
	},
	en: {
		dateDisplayFormat : 'MM/DD/YYYY'
	}
};

export function getLocale(){
	return lang;
}

export function getLocaleDateFormat() {
	return localisation[lang].dateDisplayFormat;
}

export const moment = Moment;
