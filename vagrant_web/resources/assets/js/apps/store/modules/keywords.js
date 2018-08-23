import axios from 'axios'
import {api_v1_keywords_update, api_v1_keywords_updateassignment} from './../../../components/serverRoutes'


const state = {
    keywordMaterialIds: {}
};

const getters = {

    getMaterialIdsOfCachedKeywords: (state) => (keywordId) => {
        if (state.keywordMaterialIds[keywordId]) {
            return state.keywordMaterialIds[keywordId]
        } else {
            return {};
        }
    }
};

const mutations = {


    addKeywordMaterial(state, {materialId, keyword}) {
        let pool = state.keywordMaterialIds[keyword.id] || {};

        pool[materialId] = keyword.pivot || true;
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
    }

};

const actions = {
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

    addKeywordsFromMaterial: ({commit, getters, dispatch}, {materialId, keywords}) => {

        for (let kwIndex in keywords) {
            dispatch('setKeywordFromMaterial', {materialId, keyword: keywords[kwIndex]});
        }

    },

    removeKeywordsFromMaterial: ({commit, getters, dispatch}, {materialId, keywords}) => {
        for (let kwIndex in keywords) {
            dispatch('removeKeywordFromMaterial', {materialId, keyword: keywords[kwIndex]});
        }
    },

    setKeywordFromMaterial: ({commit, getters, dispatch}, {materialId, keyword}) => {
        // Reihenfolge ist wichtig, um Pivot-Daten nicht zu verlieren (!)
        commit('addKeywordMaterial', {materialId: materialId, keyword: keyword});
    },

    removeKeywordFromMaterial: ({commit, getters, dispatch}, {materialId, keyword}) => {
        commit('removeKeywordMaterial', {materialId, keywordId: keyword.id});
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


};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};