import axios from '../../axiosInstance';
import {
    api_v1_keywords_create,
    api_v1_keywords_delete,
    api_v1_keywords_deleteassignment,
    api_v1_keywords_index,
    api_v1_keywords_show,
    api_v1_keywords_update,
    api_v1_keywords_updateassignment,
    searchGuessKeywords
} from '../../../../components/serverRoutes'
import {convertErrorResponseToMessage} from "./handleErrorsHelper";
import {clone as _clone} from 'lodash';
import {queue} from "../networkQueue";
import {getAllPages} from "../helper/paginationHelperQueued";


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
        state.keywords[keyword.id] = _clone(keyword);

        if (state.keywords[keyword.id].pivot) {
            delete state.keywords[keyword.id].pivot;
        }
    },

    removeKeyword(state, id) {
        delete state.keywords[id];
    },


    allKeywordsLoaded(state, loaded) {
        loaded = !!loaded;

        state.allKeywordsLoaded = loaded;
    },


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


    getMultiple: async ({dispatch, commit}, keywordIds) => {
        // Not tested after changing - hopefully it works :-)

        const response = Promise.all(
            keywordIds.map(
                id => queue.add(
                    () => dispatch('get', id)
                )
            )
        );


        response.then((keywords) => {
            dispatch('setMultipleKeywords', keywords);
        });

        return response;

    },

    getAll: ({dispatch, getters, commit}, forceReload) => {

        if (forceReload === true) {

            // Force reload
            commit('allKeywordsLoaded', false);

        } else if (getters.allKeywordsLoaded()) {

            // Load from cache
            return new Promise((resolve, reject) => {
                resolve(getters.getAllKeywords());
            });

        }

        const resultPromise = getAllPages(api_v1_keywords_index);

        resultPromise.then((allKeywords) => {
            dispatch('setMultipleKeywords', allKeywords);
            commit('allKeywordsLoaded', true);
        });

        return resultPromise;

    },

    /**
     *
     * @param state
     * @param allKeywords
     */
    setMultipleKeywords: ({commit}, allKeywords) => {

        for (let i in allKeywords) {
            commit('setKeyword', allKeywords[i]);
        }
    },

    create: ({commit, getters, dispatch}, {title, type}) => {

        let params = {
            title: title,
            type: type || 'key'
        };

        return axios.post(api_v1_keywords_create, params)
            .then(({data}) => {

                console.info('created Keyword', data);
                commit('setKeyword', data);

                return data;
            })
            .catch((response) => {
                throw convertErrorResponseToMessage(response);
            });

    },

    createAndAssign: async ({commit, getters, dispatch}, {title, type, materialId, relevance}) => {
        const keyword          = await dispatch('create', {title, type});
        const keywordRelevance = await dispatch('updateRelevance', {materialId, keywordId: keyword.id, relevance});

        return keywordRelevance;
    },

    update: ({commit, getters, dispatch}, {id, data}) => {

        data._method = 'PUT';

        const result = axios.post(api_v1_keywords_update(id), data)
            .then((response) => {
                return response.data;
            });

        result
            .then((keyword) => {

                commit('setKeyword', keyword);

                if (keyword.id !== id) {
                    // The ID of the keyword was changed (weil der Tag mit einem anderen identischen Tag übereinstimmte und gemerged wurde)
                    // => Der geladene Tag-Baum ist nicht mehr gültig => Reload

                    commit('removeKeyword', id);
                    commit('allKeywordsLoaded', false);
                }

            })
            .catch((response) => {
                console.error(response);
            });

        return result;
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
                return response.data;
            }).catch((response) => {
                throw convertErrorResponseToMessage(response);
            });
    },

    deleteAssignment: ({commit, getters, dispatch}, {materialId, keywordId}) => {

        let params = {
            _method: 'DELETE'
        };

        return axios.post(api_v1_keywords_deleteassignment(materialId, keywordId), params)
            .then((response) => {
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

    delete: ({commit}, id) => {

        return axios.delete(api_v1_keywords_delete(id))
            .then(({data}) => {

                if (data.success !== true) {
                    throw 'Unknown error while deleting keyword with id ' + id;
                }

                commit('removeKeyword', id);

                return data.deletedAssociations;
            })
            .catch((response) => {
                throw convertErrorResponseToMessage(response);
            });

    },


    search: ({commit, getters, dispatch}, {searchText, type, page}) => {

        type = type || false;
        page = page || 1;

        let data = {
            q: searchText
        };

        if (type) {
            // String or Array allowed here
            data.t = type;
        }

        if (page) {
            data.page = page;
        }


        return axios.get(searchGuessKeywords, {params: data})
            .then(({data}) => {

                if (data instanceof Array && data.length > 0) {
                    dispatch('setMultipleKeywords', data);
                }

                return data;
            });
    },

    /**
     * Führe mehrere Keywordsuchen zeitgleich durch, merge die Ergebnisse, entferne Dublikate und gib das Ergebnis zurück
     * @param commit
     * @param dispatch
     * @param searchArray
     * @return {Promise<any[] | never>}
     */
    searchMultiple: ({commit, dispatch}, searchArray) => {

        if (!Array.isArray(searchArray)) {
            console.error('Parameter searchArray is not of type Array');
        }

        return Promise
            .all(
                searchArray.map(({searchText, type}) => {
                        return queue.add(() => {
                            return dispatch('search', {searchText, type});
                        })
                    }
                )
            )
            .then((multiKeywords) => {

                // reduce array structure by one
                const keywords = multiKeywords.flat(1);

                // remove duplicates in arrays
                return keywords.filter((element, index, collection) =>
                    index === collection.findIndex((t) => t.id === element.id)
                );

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