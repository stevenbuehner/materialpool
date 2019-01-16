import {
    api_v1_materials_copy,
    api_v1_materials_show,
    api_v1_materials_store,
    api_v1_materials_update,
    api_v2_materialresource_attach,
    api_v2_materialresource_detach,
    api_v2_materials_delete
} from '../../../../components/serverRoutes'
import axios from '../../axiosInstance';


const state = {
    materials: {},
    loadingPromise: {}
};

const getters = {
    getMaterial: (state) => (id) => {
        if (state.materials[id]) {
            return state.materials[id];
        }

        return null;
    },

    getMaterialLoadingPromise: (state) => (id) => {

        if (state.loadingPromise[id]) {

            return state.loadingPromise[id];

        } else if (state.materials[id]) {

            state.loadingPromise[id] = new Promise(function (resolve, reject) {
                resolve(state.materials[id]);
            });

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
            const mat = getters.getMaterial(id);

            if (mat) {
                return new Promise((resolve, reject) => {
                    resolve(mat);
                });
            }
        }

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
        }

        return loadingPromise;

    },

    setMaterial: ({commit, dispatch}, material) => {
        dispatch('clearMaterial', material.id);
        commit('setMaterial', material);
    },


    create: ({commit, dispatch}, {title, from_bot, description, rating, author, keywords, bibleverses}) => {

        let data = {
            title
        };

        if (from_bot === true || from_bot === false) {
            data.from_bot = from_bot;
        }

        if (description) {
            data.description = description;
        }

        if (rating) {
            data.rating = parseInt(rating);
        }

        if (author) {
            data.author = author;
        }

        if (keywords) {
            data.keywords = keywords;
        }

        if (bibleverses) {
            data.bibleverses = bibleverses;
        }

        const result = axios.post(api_v1_materials_store, data)
            .then((result) => result.data);

        result.then((material) => {
            commit('setMaterial', material);
        });

        return result;
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
        commit('clearMaterial', id);
    },

    attachResource: ({commit, getters, dispatch}, {materialId, resourceId, limitation}) => {

        const url = api_v2_materialresource_attach(materialId, resourceId);
        let data  = {};

        if (limitation && limitation.type && limitation.value) {
            data = {
                limitation: {
                    type: limitation.type,
                    value: limitation.value
                }
            }
        }

        const result = axios.post(url, data)
            .then((result) => result.data)
            .catch((err) => {
                if (err.error) {
                    return err.error;
                } else {
                    return err;
                }
            });

        // Update material-Cache
        result.then(({material}) => {
            if (material) {
                commit('clearMaterial', material.id);
                commit('setMaterial', material);
            }
        });

        // Update resource-cache
        result.then(({resource}) => {
            if (resource) {
                dispatch('resources/setResource', resource, {root: true});
            }
        });

        return result;
    },

    detachResource: ({commit, getters, dispatch}, {materialId, resourceId}) => {

        const url = api_v2_materialresource_detach(materialId, resourceId);

        const result = axios.delete(url)
            .then((result) => result.data)
            .catch((err) => {
                if (err.error) {
                    return err.error;
                } else {
                    return err;
                }
            });

        // ALWAYS (!): Update material-Cache
        result.then(({material}) => {

            if (material) {
                commit('clearMaterial', material.id);
                commit('setMaterial', material);
            }
        });

        // ALWAYS (!): Update resource-cache
        result.then(({resource}) => {
            if (resource) {
                dispatch('resources/setResource', resource, {root: true});
            }
        });

        return result;
    },

    deleteMaterial: ({commit, dispatch}, id) => {

        return axios.delete(api_v2_materials_delete(id)).then(({data}) => {

            commit('clearMaterial', id);

            return (data.success && data.success === true);

        });

    },

    copyMaterial: ({commit, dispatch}, id) => {
        return axios.get(api_v1_materials_copy(id))
            .then(({data}) => {
                const material = data;

                commit('setMaterial', material);

                if (material.resources && Array.isArray(material.resources)) {
                    material.resources.forEach((el) => {
                        dispatch('resources/clearResource', el.id, {root: true});
                    });
                }

                return material;
            }).catch(({message}) => {
                throw message;
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