import axios from 'axios'
import {
    api_v1_bibleverse_deleteassignment,
    api_v1_bibleverse_updateassignment,
    api_v1_bibleverses_create,
    api_v1_bibleverses_show,
    searchGuessBibleverses
} from '../../../../components/serverRoutes'

import {queue} from "../networkQueue";

const state = {
    bibleverses: {},
};

const getters = {


    getBibleverse: (state) => (bibleverseId) => {
        if (state.bibleverses[bibleverseId]) {
            return state.bibleverses[bibleverseId];
        } else {
            return false;
        }
    }
};

const mutations = {


    clearBibleverse(state, bibleverseId) {
        delete state.bibleverses[bibleverseId];
    },

    setBibleverse(state, bibleverse) {
        state.bibleverses[bibleverse.id] = bibleverse;
    }

};

const actions = {
    get: ({getters, commit}, bibleverseId) => {

        const biblevers = getters.getBibleverse(bibleverseId);

        if (biblevers) {
            return new Promise((resolve, reject) => {
                resolve(biblevers);
            });
        }

        return axios.get(api_v1_bibleverses_show(bibleverseId))
            .then(({data}) => {
                commit('setBibleverse', data);
                return data;
            });
    },

    getMultiple: async ({dispatch}, bibleverseIds) => {
        // Not tested after changing - hopefully it works :-)

        return Promise.all(
            bibleverseIds.map(
                id => queue.add(
                    () => dispatch('get', id)
                )
            )
        );

    },

    create: ({commit, getters, dispatch}, {from, to}) => {

        let params = {
            from: from,
            to: to,
        };

        return axios.post(api_v1_bibleverses_create, params)
            .then(({data}) => {
                console.log('Bibleverse created', data);
                return data;
            });
    },

    createAndAssign: async ({commit, getters, dispatch}, {from, to, materialId, relevance}) => {
        const bibleverse          = await dispatch('create', {from, to});
        const bibleverseRelevance = await dispatch('updateRelevance', {
            materialId,
            bibleverseId: bibleverse.id,
            relevance
        });

        return bibleverseRelevance;
    },

    updateRelevance: ({commit, getters, dispatch}, {materialId, bibleverseId, relevance}) => {

        const data = {
            _method: 'PUT',
            relevance: relevance
        };

        // Only assignment (without setting relevance)
        if (!relevance) {
            delete data.relevance;
        }

        return axios.post(api_v1_bibleverse_updateassignment(materialId, bibleverseId), data)
            .then(({data}) => {
                return data;
            });
    },

    deleteAssignment: ({commit, getters, dispatch}, {materialId, bibleverseId}) => {

        let params = {
            _method: 'DELETE'
        };

        return axios.post(api_v1_bibleverse_deleteassignment(materialId, bibleverseId), params)
            .then(({data}) => {
                    return data;
                }
            );
    },

    deleteMultipleAssignemts: async ({dispatch}, {bibleverseIds, materialId}) => {

        return Promise.all(
            bibleverseIds.map(
                id => queue.add(
                    () => dispatch('deleteAssignment', {materialId, bibleverseId: id})
                )
            )
        );

    },

    search: ({commit, getters, dispatch}, searchText) => {

        const data = {q: searchText};

        return axios.post(searchGuessBibleverses, data)
            .then(({data}) => {
                return data;
            }).catch((response) => {
                throw response.data;
            });

    }


};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};