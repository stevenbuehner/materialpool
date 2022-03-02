import {api_v1_biblecontents_get, api_v1_biblecontents_search_and_get} from '../../../../components/serverRoutes';
import axios                                                           from '../../axiosInstance';
import {queue}                                                         from "../networkQueue";
import {getRangeId}                                                    from "../helper/bibleverseHelper";


const state = {
	cache: {}
};

const getters = {
	getBibleverse: (state) => (verseKey) => {
		if (state.cache[verseKey]) {
			return state.cache[verseKey];
		}

		return false;
	},

};

const mutations = {
	setBibleverse(state, {verseKey, verses}) {
		state.cache[verseKey] = verses;
	},
}

const actions = {

	get: async ({commit, getters, dispatch, state}, {from, to, bibleUuid}) => {

		bibleUuid      = bibleUuid || null;
		const cacheKey = getRangeId(from, to, bibleUuid);
		const cache    = getters.getBibleverse(cacheKey);

		if (cache === false) {
			// not loaded yet => load first time

			const route = api_v1_biblecontents_get(from, to, bibleUuid);

			const promise = queue.add(() => {
				return axios.get(route);
			});

			const {data} = await promise;

			// Cache Promise - falls gleichzeitig der gleiche Aufruf nochmal kommt
			commit('setBibleverse', {verseKey: cacheKey, verses: promise});

			const bible  = data.bible;
			const verses = data.verses;

			// Cache bible
			commit('bibles/addBible', bible, {root: true});

			// Cache verses ohne BibleUid
			if (bibleUuid === null) {
				commit('setBibleverse', {verseKey: cacheKey, verses});
			}

			// Cache verses inklusive BibleUid
			commit('setBibleverse', {verseKey: getRangeId(from, to, bible.uuid), verses});

			return verses;
		} else if (Array.isArray(cache)) {
			// wurde bereits gecached
			return cache;
		} else {
			// Anfrage läuft bereits => es handelt sich um ein Promise
			return cache;
		}

	},

	getMultiple: ({dispatch}, multipleQuerries) => {

		/*
		return Promise.all(
			multipleQuerries.map(fromToBible => {
					return queue.add(
						() => dispatch('get', fromToBible)
					)
				}
			)
		);
		*/

		return Promise.all(
			multipleQuerries.map(fromToBible => dispatch('get', fromToBible))
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

				            state.cache[getRangeId(from, to, bibleUuid)] = data.verses;

				            if (bibleUuid === null) {
					            state.cache[getRangeId(from, to, data.bible.uuid)] = data.verses;
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