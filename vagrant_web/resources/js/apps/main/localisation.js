// @see https://github.com/martinlindhe/laravel-vue-i18n-generator
import translations      from './../../lang-js-translation.js';
import Dayjs             from "dayjs";
import customParseFormat from 'dayjs/plugin/customParseFormat';
import localizedFormat   from 'dayjs/plugin/localizedFormat';
import localeData        from 'dayjs/plugin/localeData';

// https://day.js.org/docs/en/parse/is-valid
Dayjs.extend(customParseFormat);
Dayjs.extend(localizedFormat);
Dayjs.extend(localeData);

// or however you determine your current app locale


const dayJsLocales = {
	de: () => import('dayjs/locale/de'),
	en: () => import('dayjs/locale/en'),
	fr: () => import('dayjs/locale/fr')
}

export const vueLangConfig = {
	messages: translations,
	locale: 'de',
	fallback: 'en'
};

async function loadLocale(language) {

	// EN is already the default
	// dayjs.locale('en');

	if (dayJsLocales.hasOwnProperty(language)) {
		await dayJsLocales[language]();
	}

}


export function getLocale() {
	return lang;
}

export function getLocaleDateFormat() {
	return dayjs.localeData().longDateFormat('L') || 'YYYY/DD/MM';
}

export const dayjs = Dayjs;

export const lang = document.documentElement.lang.substring(0, 2);
loadLocale(lang);