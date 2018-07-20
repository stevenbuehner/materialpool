import Vue from 'vue';
import VueI18n from 'vue-i18n';
import MaterialApp from './App.vue';
import axios from 'axios';
import VueFlashMessage from 'vue-flash-message';
import VueRouter from 'vue-router';
import {routes} from './routes';
import {store} from './../store/index';

require('./../../localisation');
require('vue-flash-message/dist/vue-flash-message.min.css')


Vue.use(VueI18n);
Vue.use(VueFlashMessage);
Vue.use(VueRouter);

axios.defaults.headers.common = {
    'X-CSRF-TOKEN': window.Laravel.csrfToken,
    'X-Requested-With': 'XMLHttpRequest'
};

require('jquery-bar-rating');

if (materialpool && materialpool.store) {
    console.debug('Adding Global Data to store');
    store.state = {...store.state, ...materialpool.store};
}

let vueInstance = new Vue({
    el: '#app',
    render: h => h(MaterialApp),
    i18n: materialpool.i18n,
    router: new VueRouter({routes}),
    store
});

if (materialpool && materialpool.materials) {
    console.debug('Adding Global Data to store');
    vueInstance.$store.state.materials = materialpool.materials;
}

