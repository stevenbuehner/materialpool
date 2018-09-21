import axios from 'axios'
import {
    api_v1_keywords_create,
    api_v1_keywords_deleteassignment,
    api_v1_keywords_show,
    api_v1_keywords_update,
    api_v1_keywords_updateassignment,
    searchGuessKeywords
} from './../../../components/serverRoutes'
import {TaskQueue} from 'cwait';

const MAX_SIMULTANEOUS_DOWNLOADS = 6;


const state = {
    keywordMaterialIds: {},
    keywords: {},
};

const getters = {

    getMaterialIdsOfCachedKeywords: (state) => (keywordId) => {
        if (state.keywordMaterialIds[keywordId]) {
            return state.keywordMaterialIds[keywordId]
        } else {
            return {};
        }
    },

    getKeyword: (state) => (keywordId) => {
        if (state.keywords[keywordId]) {
            return state.keywords[keywordId];
        } else {
            return false;
        }
    }
};

const mutations = {


    addKeywordMaterial(state, {materialId, keyword}) {
        let pool = state.keywordMaterialIds[keyword.id] || {};

        pool[materialId]                     = keyword.pivot || true;
        state.keywordMaterialIds[keyword.id] = pool;
    },

    removeKeywordMaterial(state, {materialId, keywordId}) {
        let pool = state.keywordMaterialIds[keywordId] || {};

        if (pool[materialId]) {
            delete pool[materialId];
        }

        state.keywordMaterialIds[keywordId] = pool;
    },

    clearKeywordMaterial(state, {keywordId}) {
        state.keywordMaterialIds[keywordId] = {};
    },

    setKeyword(state, keyword) {
        state.keywords[keyword.id] = keyword;
    }

};

const actions = {
    get: ({getters, commit}, keywordId) => {

        const keyword = getters.getKeyword(keywordId);

        if (keyword) {
            return new Promise((resolve, reject) => {
                resolve(keyword);
            });
        }


        return axios.get(api_v1_keywords_show(keywordId))
            .then(({data}) => {
                commit('setKeyword', data);
                return data;
            });
    },

    getMultiple: async ({dispatch}, keywordIds) => {

        const queue = new TaskQueue(Promise, MAX_SIMULTANEOUS_DOWNLOADS);

        return await Promise.all(
            keywordIds.map(
                queue.wrap(
                    async id => await dispatch('get', id)
                )
            )
        );
    },

    create: ({commit, getters, dispatch}, {title, type}) => {

        let params = {
            title: title,
            type: type || 'key'
        };

        return axios.post(api_v1_keywords_create, params)
            .then(({data}) => {

                console.info('created Keyword', data);

                return data;
            })
            .catch((response) => {
                console.error(response);
            });

    },

    createAndAssign: async ({commit, getters, dispatch}, {title, type, materialId, relevance}) => {
        const keyword          = await dispatch('create', {title, type});
        const keywordRelevance = await  dispatch('updateRelevance', {materialId, keywordId: keyword.id, relevance});

        return keywordRelevance;
    },

    update: ({commit, getters, dispatch}, {id, data}) => {

        data._method = 'PUT';

        return axios.post(api_v1_keywords_update(id), data)
            .then((response) => {

                dispatch('updateMaterialsWithKeywordProperties', response.data);

                return response.data;

            }).catch((response) => {
                return response;
            });
    },

    updateRelevance: ({commit, getters, dispatch}, {materialId, keywordId, relevance}) => {

        const data = {
            _method: 'PUT',
            relevance: relevance
        };

        // Only assignment (without setting relevance)
        if (!relevance) {
            delete data.relevance;
        }

        return axios.post(api_v1_keywords_updateassignment(materialId, keywordId), data)
            .then((response) => {

                commit('addKeywordMaterial', {
                    materialId: materialId,
                    keyword: response.data
                });

                dispatch('updateMaterialsWithKeywordProperties', response.data);

                return response.data;

            }).catch((response) => {
                return response;
            });
    },

    deleteAssignment: ({commit, getters, dispatch}, {materialId, keywordId}) => {

        let params = {
            _method: 'DELETE'
        };

        return axios.post(api_v1_keywords_deleteassignment(materialId, keywordId), params)
            .then((response) => {
                    commit('removeKeywordMaterial', {materialId, keywordId});
                    dispatch('materials/removeKeywordFromMaterial', {materialId, keywordId}, {root: true});

                    return response.data;
                }
            ).catch((response) => {
                // on failure
                console.error('Failed to remove keyword', this.myKeyword)
            });

    },

    addKeywordsFromMaterial: ({commit, getters, dispatch}, {materialId, keywords}) => {

        for (let kwIndex in keywords) {
            dispatch('setKeywordFromMaterial', {materialId, keyword: keywords[kwIndex]});
        }

    },

    removeAllKeywordsFromMaterial: ({commit, getters, dispatch}, {materialId, keywords}) => {
        for (let kwIndex in keywords) {
            commit('removeKeywordMaterial', {materialId, keywordId: keywords[kwIndex].id});
        }
    },

    setKeywordFromMaterial: ({commit, getters, dispatch}, {materialId, keyword}) => {
        // Reihenfolge ist wichtig, um Pivot-Daten nicht zu verlieren (!)
        commit('addKeywordMaterial', {materialId: materialId, keyword: keyword});
    },

    updateMaterialsWithKeywordProperties: ({commit, getters, dispatch}, keyword) => {
        let materialIds = getters.getMaterialIdsOfCachedKeywords(keyword.id);
        let keywordCopy = JSON.parse(JSON.stringify(keyword));

        if (keywordCopy.pivot) {
            delete keywordCopy.pivot;
        }

        Object.keys(materialIds).forEach((key, index) => {
            dispatch('materials/updateMaterialKeywords', {
                materialId: key,
                keyword: keywordCopy,
                pivot: materialIds[key]
            }, {root: true});
        });

    },

    search: ({commit, getters, dispatch}, searchText) => {

        const data = {q: searchText};

        return axios.get(searchGuessKeywords, {params: data})
            .then(({data}) => data);

    }


};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};