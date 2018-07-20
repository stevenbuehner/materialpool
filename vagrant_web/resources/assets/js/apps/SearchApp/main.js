import Vue from 'vue';
import VueI18n from 'vue-i18n';
import searchPage from './../../components/pages/searchPage.vue';
import axios from 'axios';

require('lodash');

require('./../../localisation');
require('vue-flash-message/dist/vue-flash-message.min.css');

Vue.use(VueI18n);

axios.defaults.headers.common = {
    'X-CSRF-TOKEN': window.Laravel.csrfToken,
    'X-Requested-With': 'XMLHttpRequest'
};


let vueInstance = new Vue({
    el: '#app',
    i18n: materialpool.i18n,
    components: {
        searchPage
    }
});

