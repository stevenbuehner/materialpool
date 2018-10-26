import {
    api_v1_bundles_index,
    api_v1_bundles_update_init,
    api_v1_bundles_update_run
} from '../../../../components/serverRoutes';
import axios from 'axios';


const state = {
    bundles: null,
    bundleInfos: null,
};

const getters = {

    getAllBundles: (state) => () => {
        return state.bundles;
    },

    getAllBundeInfos: (state) => () => {
        return state.bundleInfos;
    },

};

const mutations = {

    allBundles(state, bundles) {
        state.bundles = bundles;
    },

    allBundleInfos(state, bundleInfos) {
        state.bundleInfos = bundleInfos;
    },

    updateBundle(state, bundle) {

        if (Array.isArray(state.bundles)) {
            let b = state.bundles.find((b) => b.uuid === bundle.uuid);

            for (let i in bundle) {
                b[i] = bundle[i];
            }
        } else {
            console.error('bundle.js has no Array to update the bundle to', bundle);
        }
    }

};

const actions = {

    allBundles: ({commit, getters, dispatch}, force) => {

        if (force === true) {
            commit('allBundles', null);
            commit('allBundleInfos', null);
        }

        if (Array.isArray(getters.getAllBundles())) {

            return new Promise((resolve, reject) => {
                resolve(getters.getAllBundles());
            });

        } else if (getters.getAllBundles() === null) {

            const promise = axios.get(api_v1_bundles_index)
                .then(response => response.data);

            // store the promises
            commit('allBundles', promise.then(({bundles}) => bundles));
            commit('allBundleInfos', promise.then(({infos}) => infos));

            promise
                .then(({bundles, infos}) => {
                    commit('allBundles', bundles);
                    commit('allBundleInfos', infos);
                })
                .catch((response) => {
                    console.error(response);
                });

        }

        // Return the Promise
        return getters.getAllBundles();

    },

    allInfos: async ({commit, getters, dispatch}) => {


        if (Array.isArray(getters.getAllBundeInfos())) {
            return getters.getAllBundeInfos();
        } else {
            dispatch('allBundles');
            return getters.getAllBundeInfos();
        }

    },

    getBundle: async ({commit, getters, dispatch}, uuid) => {

        const allBundles = await dispatch('allBundles');
        const bundle     = allBundles.find((b) => b.uuid === uuid);

        if (!bundle) {
            throw new Error('No bundle with the uuid ' + uuid + ' found');
        } else {
            return bundle;
        }

    },

    getBundleInfo: async ({commit, getters, dispatch}, uuid) => {

        const allInfos = await dispatch('allInfos');

        const info = allInfos.find((b) => {
            return b.uuid === uuid
        });

        if (!info) {
            throw new Error('No info with the uuid ' + uuid + ' found');
        } else {
            return info;
        }

    },


    initUpdateJobs: ({commit, getters, dispatch}, id) => {

        return axios.post(api_v1_bundles_update_init(id))
            .then(({data}) => data)

    },

    runJobs: ({commit, getters, dispatch}, bundleId) => {


        const response = axios.post(api_v1_bundles_update_run(bundleId))
            .then(({data}) => data)
            .catch((response) => {
                console.error(response);
                return response;
            });

        response.then(({bundle}) => {
            if (bundle) {
                commit('updateBundle', bundle);
            }
        });

        return response;

    },

};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};