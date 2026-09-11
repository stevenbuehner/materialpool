import axios                          from '../../axiosInstance';
import {api_v2_keywords_suggestions,} from '../../../../components/serverRoutes'

import {queue}                         from "../networkQueue";
import {convertErrorResponseToMessage} from "./handleErrorsHelper";
import {cloneDeep}                     from 'lodash';
import {useKeywordsStore}              from '../../stores/keywords';

const state = {
	// "ID" => {'count' => int, 'items' => []}
	// count is the total count
	// items may also be a subset (i.e. first page of items - more could be loaded later)
	keywSug: {},
};

const getters = {

	/**
	 *
	 * @param state
	 * @returns {false|array}
	 */
	getKeywordSuggestions: (state) => (id) => {
		if (state.keywSug[id]?.items) {
			return state.keywSug[id].items;
		} else {
			return false;
		}
	},

	/**
	 *
	 * @param state
	 * @returns {false|int}
	 */
	getKeywordSuggestionsCount: (state) => (id) => {

		if (state.keywSug[id]?.count) {
			return state.keywSug[id].count;
		}

		return false;
	}

};

const mutations = {


	clearKeywordSuggestions(state, id) {
		delete state.keywSug[id];
	},

	setKeywordSuggestionsCount(state, {id, count}) {
		if (!state.keywSug[id]) {
			state.keywSug[id] = {
				items: [],
				count: false
			};
		}

		state.keywSug[id].count = count;
	},

	setKeywordSuggestions(state, {id, items}) {
		if (!state.keywSug[id]) {
			state.keywSug[id] = {
				items: [],
				count: false,
			};
		}

		state.keywSug[id].items = items;

	}

};


const actions = {

	get: async ({getters, commit}, {id, maximum}) => {

		// Parse Params
		if (maximum === undefined) {
			maximum = 50; // So viele werden standardmäßig bei einem Request vom Server zurückgegeben
		}


		let suggestions = getters.getKeywordSuggestions(id);
		let countTotal  = getters.getKeywordSuggestionsCount(id);

		if (suggestions !== false && countTotal !== false && (suggestions.length >= maximum || (countTotal <= maximum && countTotal === suggestions.length))) {
			return suggestions.slice(0, maximum);
		}

		const collection = [];
		let url          = api_v2_keywords_suggestions(id);

		do {

			try {
				const {data} = await axios.get(url);
				let {
					    data: suggestions,
					    next_page_url,
					    total
				    }        = data;

				collection.push(...suggestions);

				if (countTotal !== total) {
					// Update Count
					commit('setKeywordSuggestionsCount', {id, count: total});
					countTotal = total;
				}


				url = next_page_url;

			} catch (exception) {
				return convertErrorResponseToMessage(exception);
			}

		} while (url !== null && collection.length < maximum);

		// Update Collection
		commit('setKeywordSuggestions', {id, items: collection});

		// Remove "relevance" from Keyword
		const keywordsPub = collection.map((el) => {
			let kw = cloneDeep(el);

			if (kw.relevance !== undefined) {
				delete kw.relevance;
			}

			return kw;
		});
		useKeywordsStore().setMultipleKeywords(keywordsPub);

		return collection.slice(0, maximum);

	},

	getCount: async ({dispatch, getters}, id) => {

		const countTotal = getters.getKeywordSuggestionsCount(id);

		if (countTotal !== false) {
			return countTotal;
		}

		await dispatch('get', {id});

		// Reload and return
		return getters.getKeywordSuggestionsCount(id);

	},

	/**
	 *
	 * @param dispatch
	 * @param {[{from,to,maximum}]} ranges
	 * @returns {Promise<Awaited<unknown>[]>}
	 */
	getMultiple: async ({dispatch}, ranges) => {
		// Not tested after changing - hopefully it works :-)

		return Promise.all(
			ranges.map(
				({id, maximum}) => queue.add(
					() => dispatch('get', {id, maximum})
				)
			)
		);

	},

};

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations
};
