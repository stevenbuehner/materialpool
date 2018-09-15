import Vue from 'vue';
import '@babel/polyfill';
import VueI18n from 'vue-i18n';
import axios from 'axios';
import VueRouter from 'vue-router';
import {store} from './../store/index'; // Before routes to use in BeforeRouting-Functions
import {routes} from './routes';
import mainApp from './App.vue';
// Styling
import './../../../sass/app.scss';

require('lodash');

require('./../../localisation');
require('vue-flash-message/dist/vue-flash-message.min.css');

Vue.use(VueI18n);
Vue.use(VueRouter);

axios.defaults.headers.common = {
    'X-CSRF-TOKEN': window.Laravel.csrfToken,
    'X-Requested-With': 'XMLHttpRequest'
};

const router = new VueRouter({
    mode: 'history',
    base: '/vue',
    scrollBehavior(to, from, savedPosition) {
        return {x: 0, y: 0}
    },
    routes
});

let vueInstance = new Vue({
    el: '#app',
    i18n: materialpool.i18n,
    router: router,
    render: h => h(mainApp),
    components: {},
    store
});

