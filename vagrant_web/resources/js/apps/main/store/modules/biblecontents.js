import {api_v1_biblecontents_get, api_v1_biblecontents_search_and_get} from '../../../../components/serverRoutes';
import axios from '../../axiosInstance';


const state = {};

const getters = {};

const mutations = {};

const actions = {

    get: ({commit, getters, dispatch}, {from, to, bibleId}) => {

        const route = api_v1_biblecontents_get(from, to, bibleId);

        return axios.get(route)
            .then(({data}) => data);

    },

    searchAndGet: ({commit, getters, dispatch}, {search, bibleUuid}) => {

        const route = api_v1_biblecontents_search_and_get(search, bibleUuid);

        return axios.get(route, {params: {search}})
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