import {
    api_v1_resources_create_material,
    api_v1_resources_delete,
    api_v1_resources_show,
    api_v1_resources_update
} from '../../../../components/serverRoutes'
import axios from 'axios'


const state = {
    resources: {},
    loadingPromise: {}
};

const getters   = {
    updateResource: (state) => (id) => {
        if (state.resources[id]) {
            return state.resources[id];
        }

        return null;
    },

    getResourceLoadingPromise: (state) => (id) => {
        if (state.loadingPromise[id]) {
            return state.loadingPromise[id];
        } else if (state.resources[id]) {
            return state.loadingPromise[id] = new Promise(function (resolve, reject) {
                resolve(state.resources[id]);
            });
        } else {
            return false;
        }
    }
};
const mutations = {
    setResource(state, resource) {
        state.resources[resource.id] = resource;
    },

    setResourceLoadingPromise(state, {id, promise}) {
        state.loadingPromise[id] = promise;
    },

    clearResource(state, id) {
        delete state.resources[id];
        delete state.loadingPromise[id];
    }
};

const actions = {
    get: ({getters, commit, dispatch, state}, id) => {

        let loadingPromise = getters.getResourceLoadingPromise(id);

        if (loadingPromise === false) {
            loadingPromise = new Promise((resolve, reject) => {

                let res = getters.updateResource(id);

                if (res) {
                    resolve(res);
                } else {
                    axios.get(api_v1_resources_show(id), {
                        params: {
                            relations: ['materials', 'materials.keywords', 'materials.bibleverses', 'creator']
                        }
                    }).then((response) => {
                        dispatch('setResource', response.data);
                        resolve(getters.updateResource(id));
                    }).catch(() => {
                        reject('Could not find Resource');
                    });
                }
            });

            commit('setResourceLoadingPromise', {id: id, promise: loadingPromise});

        } else {
            loadingPromise = loadingPromise;
        }

        return loadingPromise;

    },

    deleteResource: ({commit}, id) => {

        return axios.delete(api_v1_resources_delete(id)).then(({data}) => {

            commit('clearResource', id);

            return (data.success && data.success === true);

        });

    },

    setResource: ({commit}, resource) => {
        commit('clearResource', resource.id);
        commit('setResource', resource);
    },

    update: ({commit, dispatch}, {id, data}) => {
        return axios.put(api_v1_resources_update(id), data)
            .then((response) => response.data)
            .then((resource) => {
                dispatch('setResource', resource);
            });
    },

    clearResource: ({commit}, id) => {
        commit('clearResource', id);
    },

    autoCreateMaterial: ({commit, dispatch}, resourceId) => {

        const promise = axios.post(api_v1_resources_create_material(resourceId))
            .then(({data}) => {
                return {
                    material: data.material,
                    resource: data.resource
                };
            });

        promise.then(({material, resource}) => {
            dispatch('setResource', resource);
            dispatch('materials/setMaterial', material, {root: true});
        });

        return promise;
    }

};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};