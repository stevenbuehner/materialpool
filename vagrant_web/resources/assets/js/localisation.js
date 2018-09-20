import Vue from 'vue';
// @see https://github.com/martinlindhe/laravel-vue-i18n-generator
import VueInternationalization from 'vue-i18n';
import Locale from './vue-i18n-locales.generated';

Vue.use(VueInternationalization);

const lang = document.documentElement.lang.substr(0, 2);
// or however you determine your current app locale

export const i18n = new VueInternationalization({
    locale: lang,
    messages: Locale
});
