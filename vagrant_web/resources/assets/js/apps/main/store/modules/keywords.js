import axios from 'axios'
import {
    api_v1_keywords_create,
    api_v1_keywords_deleteassignment,
    api_v1_keywords_index,
    api_v1_keywords_show,
    api_v1_keywords_update,
    api_v1_keywords_updateassignment,
    searchGuessKeywords
} from '../../../../components/serverRoutes'

import {queue} from "../networkQueue";

const state = {
    keywords: {},
    allKeywordsLoaded: false,
};

const getters = {

    getKeyword: (state) => (keywordId) => {
        if (state.keywords[keywordId]) {
            return state.keywords[keywordId];
        } else {
            return false;
        }
    },

    allKeywordsLoaded: (state) => () => {
        return state.allKeywordsLoaded;
    },

    getAllKeywords: (state) => () => {
        return Object.values(state.keywords);
    }
};

const mutations = {

    setKeyword(state, keyword) {
        state.keywords[keyword.id] = keyword;
    },

    /**
     *
     * @param state
     * @param Array allKeywords
     */
    setMultipleKeywords(state, allKeywords) {

        for (let i in allKeywords) {
            state.keywords[allKeywords[i].id] = allKeywords[i];
        }
    },

    allKeywordsLoaded(state, loaded) {
        loaded = !!loaded;

        state.allKeywordsLoaded = loaded;
    }

};

const actions = {
    get: ({getters, commit}, keywordId) => {

        const keyword = getters.getKeyword(keywordId);

        if (keyword) {
            return new Promise((resolve, reject) => {
                resolve(keyword);
            });
        }


        return axios.get(api_v1_keywords_show(keywordId))
            .then(({data}) => {
                commit('setKeyword', data);
                return data;
            });
    },


    getMultiple: async ({dispatch}, keywordIds) => {
        // Not tested after changing - hopefully it works :-)

        return Promise.all(
            keywordIds.map(
                id => queue.add(
                    () => dispatch('get', id)
                )
            )
        );

    },

    getAll: async ({dispatch, getters, commit}) => {

        // Load from cache
        if (getters.allKeywordsLoaded()) {

            return new Promise((resolve, reject) => {
                resolve(getters.getAllKeywords());
            });

        }

        const getPage = async (pageNo) => {

            console.log('Start RUNNING (AXIOS) for page ' + pageNo);

            return axios.get(api_v1_keywords_index,
                {
                    params: {page: pageNo}
                })
                .then(({data}) => {
                    return data
                })
                .catch((response) => {
                    console.error(response);
                });
        };


        const resultPromise = getPage(1)
            .then(data => {
                const last_page    = data.last_page;
                const current_page = data.current_page;

                if (last_page !== current_page) {

                    let allPromises = [];

                    allPromises.push(
                        queue.add(
                            () => {
                                return data.data;
                            }
                        ));

                    for (let i = 2; i <= last_page; i++) {
                        allPromises.push(
                            queue.add(
                                () => {
                                    return getPage(i).then(({data}) => data);
                                }
                            )
                        );
                    }

                    return Promise.all(allPromises).then((results) => {

                        return [].concat(...results);
                    });
                } else {
                    return data.data;
                }
            });

        resultPromise.then((allKeywords) => {
            commit('setMultipleKeywords', allKeywords);
            commit('allKeywordsLoaded', true);
        });

        return resultPromise;

    },

    create: ({commit, getters, dispatch}, {title, type}) => {

        let params = {
            title: title,
            type: type || 'key'
        };

        return axios.post(api_v1_keywords_create, params)
            .then(({data}) => {

                console.info('created Keyword', data);

                return data;
            })
            .catch((response) => {
                console.error(response);
            });

    },

    createAndAssign: async ({commit, getters, dispatch}, {title, type, materialId, relevance}) => {
        const keyword          = await dispatch('create', {title, type});
        const keywordRelevance = await  dispatch('updateRelevance', {materialId, keywordId: keyword.id, relevance});

        return keywordRelevance;
    },

    update: ({commit, getters, dispatch}, {id, data}) => {

        data._method = 'PUT';

        return axios.post(api_v1_keywords_update(id), data)
            .then((response) => {

                // dispatch('updateMaterialsWithKeywordProperties', response.data);

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

        // Only assignment (without setting relevance)
        if (!relevance) {
            delete data.relevance;
        }

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

    deleteAssignment: ({commit, getters, dispatch}, {materialId, keywordId}) => {

        let params = {
            _method: 'DELETE'
        };

        return axios.post(api_v1_keywords_deleteassignment(materialId, keywordId), params)
            .then((response) => {
                    dispatch('materials/removeKeywordFromMaterial', {materialId, keywordId}, {root: true});

                    return response.data;
                }
            ).catch((response) => {
                // on failure
                console.error('Failed to remove keyword', this.myKeyword)
            });

    },

    deleteMultipleAssignemts: async ({dispatch}, {keywordIds, materialId}) => {

        return Promise.all(
            keywordIds.map(
                id => {
                    return queue.add(
                        () => dispatch('deleteAssignment', {materialId, keywordId: id})
                    )
                }
            )
        );

    },

    addKeywordsFromMaterial: ({commit, getters, dispatch}, {materialId, keywords}) => {

        for (let kwIndex in keywords) {
            dispatch('setKeywordFromMaterial', {materialId, keyword: keywords[kwIndex]});
        }

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

    search: ({commit, getters, dispatch}, searchText) => {

        const data = {q: searchText};

        return axios.get(searchGuessKeywords, {params: data})
            .then(({data}) => data);

    }


};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};