import {api_v1_materials_show, api_v1_materials_update} from './../../../components/serverRoutes'
import axios from 'axios'


const state = {
    materials: {},
    loadingPromise: {}
};

const getters   = {
    getMaterial: (state) => (id) => {
        if (state.materials[id]) {
            return state.materials[id];
        }

        return null;
    },

    getMaterialLoadingPromise: (state) => (id) => {
        if (state.loadingPromise[id]) {
            return state.loadingPromise[id];
        } else {
            return false;
        }
    }
};
const mutations = {
    setMaterial(state, material) {
        state.materials[material.id] = material;
    },

    setMaterialLoadingPromise(state, {id, promise}) {
        state.loadingPromise[id] = promise;
    },

    clearMaterial(state, id) {
        delete state.materials[id];
        delete state.loadingPromise[id];
    }
};

const actions = {
    getMaterial: ({getters, commit, dispatch, state}, id) => {

        let loadingPromise = getters.getMaterialLoadingPromise(id);

        if (loadingPromise === false) {
            loadingPromise = new Promise((resolve, reject) => {

                let res = getters.getMaterial(id);

                if (res) {
                    resolve(res);
                } else {
                    axios.get(api_v1_materials_show(id), {})
                        .then((response) => {
                            dispatch('setMaterial', response.data);
                            resolve(getters.getMaterial(id));
                        }).catch(() => {
                        reject('Could not find Material with id ' + id);
                    });
                }
            });

            commit('setMaterialLoadingPromise', {id: id, promise: loadingPromise});

        } else {
            loadingPromise = loadingPromise;
        }

        return loadingPromise;

    },

    setMaterial: ({commit, dispatch}, material) => {
        dispatch('clearMaterial', material.id);
        commit('setMaterial', material);
        dispatch('keywords/addKeywordsFromMaterial', {
            materialId: material.id,
            keywords: material.keywords
        }, {root: true});
    },

    updateMaterial: ({commit, getters, dispatch}, {id, data}) => {

        data._method = 'PUT';

        const result = axios.post(api_v1_materials_update(id), data);

        result.then((response) => {

            dispatch('setMaterial', response.data);
            return getters.getMaterial(id);

        }).catch((er) => {
            console.error(er);
        });

        return result;
    },

    /**
     * Aktualisiere ein Keyword bei einem Material
     * @param commit
     * @param getters
     * @param dispatch
     * @param materialId
     * @param keyword
     * @param pivot
     */
    updateMaterialKeywords: ({commit, getters, dispatch}, {materialId, keyword, pivot}) => {

        let mat = getters.getMaterial(materialId);
        console.info('Received Update request');

        if (mat && mat.keywords) {

            let found = mat.keywords.find(kw => kw.id == keyword.id);

            if (!found) {
                found = keyword;
                mat.keywords.push(found);
            } else {

                for (let prop in keyword) {
                    found[prop] = keyword[prop];
                }

            }


            // Update pivot
            if (pivot) {
                found.pivot = pivot;
            }

            commit('setMaterial', mat);
        }

    },

    addKeywordToMaterial: ({commit, getters, dispatch}, {materialId, keyword, relevance}) => {

        let mat = getters.getMaterial(materialId);

        if (mat) {
            const found = mat.keywords.find(el => el.id == keyword.id);

            if (found === undefined) {
                keyword.pivot = {relevance: relevance}
                mat.keywords.push(keyword);
                commit('setMaterial', mat);
            } else {
                dispatch('updateMaterialKeywords', {materialId, keyword, pivot: {relevance}});
            }
        }

    },

    removeKeywordFromMaterial: ({commit, getters, dispatch}, {materialId, keywordId}) => {

        let mat = getters.getMaterial(materialId);

        if (mat && mat.keywords) {
            mat.keywords = mat.keywords.filter((el) => {
                return el.id != keywordId
            });

            commit('setMaterial', mat);
        }
    },


    clearMaterial: ({commit, getters, dispatch}, id) => {

        let mat = getters.getMaterial(id);

        if (mat && mat.keywords && mat.keywords.length > 0) {
            dispatch('keywords/removeAllKeywordsFromMaterial', {materialId: id, keywords: mat.keywords}, {root: true});
        }

        commit('clearMaterial', id);
    }

};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};