import Vue                     from 'vue';
// @see https://github.com/martinlindhe/laravel-vue-i18n-generator
import VueInternationalization from 'vue-i18n';
import Locale                  from '../../vue-i18n-locales.generated';
import {de, en}                from 'vuejs-datepicker/dist/locale';


Vue.use(VueInternationalization);

export const lang = document.documentElement.lang.substr(0, 2);
// or however you determine your current app locale


export const i18n = new VueInternationalization({
	locale: lang,
	messages: Locale
});


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
