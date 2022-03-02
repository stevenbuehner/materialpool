import {getAllPages}                             from "../helper/paginationHelperQueued";
import {api_v1_bibles_index, api_v1_bibles_show} from "../../../../components/serverRoutes";
import axiosInstance                             from "../../axiosInstance";

const state = {
	allBibles: {},
	allBiblesLoaded: false
};

const getters = {
	getBible: (state) => (uuid) => {
		return state.allBibles[uuid];
	}
};

const mutations = {
	addBible(state, bible) {
		state.allBibles[bible.uuid] = bible;
	}
};

const actions = {

	get: async ({state, commit, getters}, uuid) => {

		const value = getters.getBible(uuid);

		if (value === undefined) {

			const {data} = await axiosInstance.get(api_v1_bibles_show(uuid));
			commit('addBible', data);

			return data;

		} else {
			return value;
		}

	},

	getAll: ({state, commit, getters, dispatch}) => {

		if (state.allBiblesLoaded === false) {

			// first Time run -> run Query
			return state.allBiblesLoaded = getAllPages(api_v1_bibles_index)
				.then(data => {
					for (let i in data) {
						commit('addBible', data[i]);
					}

					state.allBiblesLoaded = true;

					return state.allBibles.values();
				});

		} else if (state.allBiblesLoaded === true) {

			// is Object
			return new Promise((resolve, reject) => {
				resolve(Object.values(state.allBibles));
			});

		} else {

			// Is Promise
			return state.allBiblesLoaded;

		}


	},

};

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations
};