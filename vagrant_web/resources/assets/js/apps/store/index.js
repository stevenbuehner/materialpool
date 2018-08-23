import Vue from 'vue';
import VueX from 'vuex';
import resources from './modules/resources';
import materials from './modules/materials';
import keywords from './modules/keywords';

Vue.use(VueX);

export const store = new VueX.Store({

    modules: {
        resources,
        materials,
        keywords
    }
});