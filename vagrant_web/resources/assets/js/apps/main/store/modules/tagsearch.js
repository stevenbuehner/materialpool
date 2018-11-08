import {searchGuessRoute} from '../../../../components/serverRoutes';
import axios from 'axios';


const state = {};

const getters   = {};
const mutations = {};

const actions = {

    searchTags: ({commit, getters, dispatch}, searchText) => {

        const data = {q: searchText};

        return axios.get(searchGuessRoute, {params: data})
            .then(({data}) => {

                return data;
            })
            .catch((response) => {
                console.error(response);
                return response;
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