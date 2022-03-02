import axios                                 from '../../axiosInstance';
import {api_v2_bibleverse_cross_references,} from '../../../../components/serverRoutes'

import {queue}                         from "../networkQueue";
import {convertErrorResponseToMessage} from "./handleErrorsHelper";
import {getRangeId}                    from "../helper/bibleverseHelper";

const state = {
	// "1001001-1001001" => {'count' => int, 'items' => []}
	// count is the total count
	// items may also be a subset (i.e. first page of items - more could be loaded later)
	crossRefs: {},
};

const getters = {

	/**
	 *
	 * @param state
	 * @returns {false|array}
	 */
	getCrossRefs: (state) => (rangeId) => {
		if (state.crossRefs[rangeId]?.items) {
			return state.crossRefs[rangeId].items;
		} else {
			return false;
		}
	},

	/**
	 *
	 * @param state
	 * @returns {false|int}
	 */
	getCrossRefsCount: (state) => (rangeId) => {

		if (state.crossRefs[rangeId]?.count) {
			return state.crossRefs[rangeId].count;
		}

		return false;
	}

};

const mutations = {


	clearCrossRefs(state, rangeId) {
		delete state.crossRefs[rangeId];
	},

	setCrossRefsCount(state, {rangeId, count}) {
		if (!state.crossRefs[rangeId]) {
			state.crossRefs[rangeId] = {
				items: [],
				count: false
			};
		}

		state.crossRefs[rangeId].count = count;
	},

	setCrossRefs(state, {rangeId, refs}) {
		if (!state.crossRefs[rangeId]) {
			state.crossRefs[rangeId] = {
				items: [],
				count: false,
			};
		}

		state.crossRefs[rangeId].items = refs;

	}

};



const actions = {

	get: async ({getters, commit}, {from, to, maximum}) => {

		// Parse Params
		if (maximum === undefined) {
			maximum = 50; // So viele werden standardmäßig bei einem Request vom Server zurückgegeben
		}

		if (to === undefined) {
			to = from;
		}

		const rangeId  = getRangeId(from, to);
		let crossRefs  = getters.getCrossRefs(rangeId);
		let countTotal = getters.getCrossRefsCount(rangeId);

		if (crossRefs !== false && countTotal !== false && (crossRefs.length >= maximum || (countTotal <= maximum && countTotal === crossRefs.length))) {
			return crossRefs.slice(0, maximum);
		}

		const collection = [];
		let url          = api_v2_bibleverse_cross_references(from, to);

		do {

			try {
				const {data} = await axios.get(url);
				let {
					    data: crossRefs,
					    next_page_url,
					    total
				    }        = data;

				collection.push(...crossRefs);

				if (countTotal !== total) {
					// Update Count
					commit('setCrossRefsCount', {rangeId, count: total});
					countTotal = total;
				}


				url = next_page_url;

			} catch (exception) {
				return convertErrorResponseToMessage(exception);
			}

		} while (url !== null && collection.length < maximum);

		// Update Collection
		commit('setCrossRefs', {rangeId, refs: collection});

		return collection.slice(0, maximum);

	},

	getCount: async ({dispatch, getters}, {from, to}) => {

		if (to === undefined) {
			to = from;
		}

		const rangeId    = getRangeId(from, to);
		const countTotal = getters.getCrossRefsCount(rangeId);

		if (countTotal !== false) {
			return countTotal;
		}

		await dispatch('get', {from, to});

		// Reload and return
		return getters.getCrossRefsCount(rangeId);

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
				({from, to, maximum}) => queue.add(
					() => dispatch('get', {from, to, maximum})
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