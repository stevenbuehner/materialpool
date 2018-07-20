import Vue from 'vue';
import VueX from 'vuex';
import material from './modules/material';

Vue.use(VueX);

export const store = new VueX.Store({

    modules: [material],


});