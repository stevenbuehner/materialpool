import {api_v1_biblecontents_get, api_v1_biblecontents_search_and_get} from '../../../../components/serverRoutes';
import axios from '../../axiosInstance';
import {queue} from "../networkQueue";

const state = {};

const getters = {};

const mutations = {};

const actions = {

    get: ({commit, getters, dispatch}, {from, to, bibleUuid}) => {

        const route = api_v1_biblecontents_get(from, to, bibleUuid);

        return axios.get(route)
            .then(({data}) => data);

    },

    getMultiple: ({dispatch}, multipleQuerries) => {

        return Promise.all(
            multipleQuerries.map(fromToBible => {
                    return queue.add(
                        () => dispatch('get', fromToBible)
                    )
                }
            )
        );

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