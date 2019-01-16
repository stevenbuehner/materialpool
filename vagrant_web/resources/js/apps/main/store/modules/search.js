import {searchUrl} from '../../../../components/serverRoutes';
import axios from '../../axiosInstance';

const MAX_CACHE_HISTORY = 20;


const state = {
    selectedSearchValues: {},

    searchCacheHistory: [],
    searchCache: {}
};

const getters = {

    hasCacheEntry: (state) => (query) => {
        const json = JSON.stringify(query);

        return !!state.searchCache[json];
    },

    getCacheEntry: (state) => (query) => {
        const json    = JSON.stringify(query);
        const promise = state.searchCache[json];

        let myIndex = null;
        state.searchCacheHistory.find((el, index) => {
            myIndex = index;
            return el === json;
        });


        // Put requested Cache at last position in array
        if (myIndex !== null) {
            state.searchCacheHistory.push(state.searchCacheHistory.splice(myIndex, 1)[0]);

        }

        return promise;

    }

};

const mutations = {

    putSearchCache(state, {query, promise}) {

        const jsonQuery = JSON.stringify(query);

        // Does cache already exist?
        if (state.searchCache[jsonQuery]) {

            // => Remove Cached Element (don't need it twice)
            state.searchCacheHistory = state.searchCacheHistory.filter((el) => {
                return el !== jsonQuery;
            });

        } else if (state.searchCacheHistory.length >= MAX_CACHE_HISTORY) {

            // Remove first element of array
            const firstIndex = state.searchCacheHistory.shift();

            // Delete the cache
            if (state.searchCache[firstIndex]) {
                delete state.searchCache[firstIndex];
            }

        }

        // Add new Cache Entry to the back
        state.searchCache[jsonQuery] = promise;
        state.searchCacheHistory.push(jsonQuery);

    },

    setSelectedSearchValues(state, value) {
        state.selectedSearchValues = value;
    }
};

const actions = {


    materials: ({commit, getters, dispatch}, {query, page}) => {

        page = page || 1;

        var data = {
            q: query,
            page: page
        };

        let resultPromise = null;

        if (getters.hasCacheEntry(data)) {

            console.log('Using cached Searchresults for:', query);

            resultPromise = getters.getCacheEntry(data);

        } else {

            console.log('updating Searchresults for:', query);

            resultPromise = axios.post(searchUrl, data)
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

                })
                .catch(({response}) => {
                    throw response.message;
                });

        }

        commit('putSearchCache', {query: data, promise: resultPromise});

        return resultPromise;
    },

    materialsWithParams: ({commit, getters, dispatch}, {material, page}) => {

        let searchQuery = [];

        if (material.title) {
            searchQuery.push({
                type: '*',
                text: material.title
            });
        }

        return dispatch('materials', {query: [searchQuery], page});

    },

};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};