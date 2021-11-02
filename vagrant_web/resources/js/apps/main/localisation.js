// @see https://github.com/martinlindhe/laravel-vue-i18n-generator
import {de, en}     from 'vuejs-datepicker/dist/locale';
import translations from './../../lang-js-translation.js';

// or however you determine your current app locale
export const lang = document.documentElement.lang.substr(0, 2);

export const vueLangConfig = {
	messages: translations,
	locale: 'de',
	fallback: 'en'
};

export const localisation = {
	de: {
		datepicker: de,
		dateDisplayFormat: 'dd.MM.yyyy'
	},
	en: {
		datepicker: en,
		dateDisplayFormat: 'MM/dd/yyyy'
	}
};
