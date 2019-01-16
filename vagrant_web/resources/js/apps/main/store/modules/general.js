import {api_v1_general_options} from '../../../../components/serverRoutes';
import axios from '../../axiosInstance';


const state = {
    options: null,
};

const getters = {

    getOptions: (state) => () => {
        return state.options;
    },

};

const mutations = {

    setOptions(state, options) {
        state.options = options;
    },

};

const actions = {

    options: ({getters, commit}) => {

        const opt = getters.getOptions();

        if (opt === null) {
            const promise = axios.get(api_v1_general_options)
                .then(({data}) => {

                    return data;

                })
                .catch(({message}) => {
                    throw message;
                });

            mutations.setOptions(promise);

            return promise;

        } else if (typeof opt.then === "function") {
            return opt;
        }

        console.error('this should not happen in general.js');

    },

    maxUploadSize: ({dispatch}) => {
        return dispatch('options').then((allOptions) => {
            return allOptions.server.max_upload;
        });
    },

    currentUser: ({dispatch}) => {
        return dispatch('options').then((allOptions) => {
            return allOptions.user;
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