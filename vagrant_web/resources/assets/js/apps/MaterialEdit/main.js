import Vue from 'vue';
import VueI18n from 'vue-i18n';
import MaterialDetail from './../../components/Material/MaterialDetail.vue';
import MaterialCardListing from './../../components/Material/MaterialCardListing.vue';
import axios from 'axios';
import VueFlashMessage from 'vue-flash-message';

require('./../../localisation');
require('vue-flash-message/dist/vue-flash-message.min.css');


Vue.use(VueI18n);
Vue.use(VueFlashMessage);

axios.defaults.headers.common = {
    'X-CSRF-TOKEN': window.Laravel.csrfToken,
    'X-Requested-With': 'XMLHttpRequest'
};

require('jquery-bar-rating');

// console.log(materialpool.material);

let vueInstance = new Vue({
    el: '#app',
    i18n: materialpool.i18n,
    components: {
        MaterialDetail,
        MaterialCardListing
    }
});

