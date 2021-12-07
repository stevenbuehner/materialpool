import {api_v1_biblecontents_get, api_v1_biblecontents_search_and_get} from '../../../../components/serverRoutes';
import axios                                                           from '../../axiosInstance';
import {queue}                                                         from "../networkQueue";

function verseKey(from, to, bibleuuid) {
	return from + '-' + to + '-' + bibleuuid;
}

const state = {
	cache: {}
};

const getters = {};

const mutations = {};


const actions = {

	get: ({commit, getters, dispatch, state}, {from, to, bibleUuid}) => {

		bibleUuid      = bibleUuid || null;
		const cacheKey = verseKey(from, to, bibleUuid);

		if (state.cache[cacheKey]) {

			return new Promise((resolve, reject) => {
				resolve(state.cache[cacheKey]);
			});

		} else {

			const route = api_v1_biblecontents_get(from, to, bibleUuid);

			return axios.get(route)
			            .then(({data}) => {

				            const bible  = data.bible;
				            const verses = data.verses;

				            // Cache verses
				            state.cache[cacheKey] = verses;

				            // Cache with bibleUuid if not done yet
				            if (bibleUuid === null) {
					            state.cache[verseKey(from, to, bible.uuid)] = verses;
				            }

				            // Cache bible
				            commit('bibles/addBible', bible, {root: true});

				            return verses;

			            });

		}


	},

	getMultiple: ({dispatch}, multipleQuerries) => {

		return Promise.all(
			multipleQuerries.map(fromToBible => {
					return queue.add(
						() => dispatch('get', fromToBible)
					)
				}
			)
		);

	},

	searchAndGet: ({commit, getters, dispatch}, {search, bibleUuid}) => {

		const route = api_v1_biblecontents_search_and_get(search, bibleUuid);

		return axios.get(route, {params: {search}})
		            .then(({data}) => {

			            if (data.error) {
							console.error(data.error);
				            throw(data.error);
			            }

			            // data: {bible, bibleverses, verses}
			            commit('bibles/addBible', data.bible, {root: true});

			            if (data.bibleverses.length === 1) {
				            // All bibleverses belong to this one bibleverse
				            const from = data.bibleverses[0].from;
				            const to   = data.bibleverses[0].to;

				            state.cache[verseKey(from, to, bibleUuid)] = data.verses;

				            if (bibleUuid === null) {
					            state.cache[verseKey(from, to, data.bible.uuid)] = data.verses;
				            }
			            }

			            return data;
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