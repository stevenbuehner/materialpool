import {api_v1_biblecontents_get} from '../../../../components/serverRoutes';
import axios from 'axios';


const state = {};

const getters = {};

const mutations = {};

const actions = {

    get: ({commit, getters, dispatch}, {from, to, bibleId}) => {

        const route = api_v1_biblecontents_get(from, to, bibleId);

        return axios.get(route)
            .then(({data}) => data);

    },


};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};