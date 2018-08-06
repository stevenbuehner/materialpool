import Vue from 'vue';
import VueI18n from 'vue-i18n';
import axios from 'axios';
import VueRouter from 'vue-router';
import {routes} from './routes';
import mainApp from './App.vue';

require('lodash');

require('./../../localisation');
require('vue-flash-message/dist/vue-flash-message.min.css');

Vue.use(VueI18n);
Vue.use(VueRouter);

axios.defaults.headers.common = {
    'X-CSRF-TOKEN': window.Laravel.csrfToken,
    'X-Requested-With': 'XMLHttpRequest'
};


let vueInstance = new Vue({
    el: '#app',
    i18n: materialpool.i18n,
    router: new VueRouter({routes}),
    render: h => h(mainApp),
    components: {}
});

