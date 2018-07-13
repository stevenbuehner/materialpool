import Vue from 'vue';
require('./../../localisation');
import VueI18n from 'vue-i18n';
import MaterialApp from './App.vue';
import axios from 'axios';
import VueFlashMessage from 'vue-flash-message';

require('vue-flash-message/dist/vue-flash-message.min.css')


Vue.use(VueI18n);
Vue.use(VueFlashMessage);

axios.defaults.headers.common = {
    'X-CSRF-TOKEN': window.Laravel.csrfToken,
    'X-Requested-With': 'XMLHttpRequest'
};

require('jquery-bar-rating');

new Vue({
    el: '#app',
    render: h => h(MaterialApp),
    i18n: materialpool.i18n
});

