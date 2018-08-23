import {api_v1_resources_show} from './../../../components/serverRoutes'
import axios from 'axios'


const state = {
    resources: {},
    loadingPromise: {}
};

const getters   = {
    getResource: (state) => (id) => {
        if (state.resources[id]) {
            return state.resources[id];
        }

        return null;
    },
    getResourceLoadingPromise: (state) => (id) => {
        if (state.loadingPromise[id]) {
            return state.loadingPromise[id];
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
    getResource: ({getters, commit, dispatch, state}, id) => {

        let loadingPromise = getters.getResourceLoadingPromise(id);

        if (loadingPromise === false) {
            loadingPromise = new Promise((resolve, reject) => {

                let res = getters.getResource(id);

                if (res) {
                    resolve(res);
                } else {
                    axios.get(api_v1_resources_show(id), {
                        params: {
                            relations: ['materials', 'materials.keywords', 'materials.bibleverses']
                        }
                    }).then((response) => {
                        dispatch('setResource', response.data);
                        resolve(getters.getResource(id));
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

    setResource: ({commit}, resource) => {
        commit('setResource', resource);
    },

    updateResource: ({commit}, {id, data}) => {

    },

    clearResource: ({commit}, id) => {
        commit('clearResource', id);
    }

};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};