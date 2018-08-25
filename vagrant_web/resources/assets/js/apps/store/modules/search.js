import {searchUrl} from './../../../components/serverRoutes';
import axios from 'axios';


const state = {
    selectedSearchValues: {},

    lastSearchQueryJson: '',
    lastSearchPromise: null,
};

const getters   = {
    getLastSearchPromise: (state) => {
        return state.lastSearchPromise;
    },
    getLastSearchQueryAsJson: (state) => {
        return state.lastSearchQueryJson;
    }
};
const mutations = {
    setSearchValues(state, values) {
        state.selectedSearchValues = values;
    },

    setLastSearchPromise(state, value) {
        state.lastSearchPromise = value;
    },

    setLastSearchQuery(state, value) {
        state.lastSearchQueryJson = JSON.stringify(value);
    },

    setLastSearchQueryAsJson(state, value) {
        state.lastSearchQueryJson = value;
    }
};

const actions = {


    materials: ({commit, getters, dispatch}, {query, page}) => {

        page = page || 1;

        var data = {
            q: query,
            page: page
        };

        const current = JSON.stringify(data);
        const last    = getters.getLastSearchQueryAsJson;

        if (current === last) {
            console.log('Using cached Searchresults for:', query);

            return getters.getLastSearchPromise;
        }

        console.log('updating Searchresults for:', query);

        const resultPromise = axios.post(searchUrl, data)
            .then(response => response.data)
            .then((data) => {

                const materials = data.data;
                // const materialIds = materials.map(m => m.id);
                const paging    = {
                    current_page: data.current_page,
                    from: data.from,
                    last_page: data.last_page,
                    next_page_url: data.next_page_url,
                    per_page: data.per_page,
                    prev_page_url: data.prev_page_url,
                    to: data.to,
                    total: data.total,
                };

                for (let i in materials) {
                    dispatch('materials/setMaterial', materials[i], {root: true});
                }

                return {materials, paging};

            });

        commit('setLastSearchPromise', resultPromise);
        commit('setLastSearchQuery', data);

        return resultPromise;
    },

};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};