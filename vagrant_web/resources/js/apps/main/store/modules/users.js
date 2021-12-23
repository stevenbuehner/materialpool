import axios                           from '../../axiosInstance';
import {convertErrorResponseToMessage} from "./handleErrorsHelper";
import {api_v2_users_find}             from "../../../../components/serverRoutes";


const state = {
	users: {},
};

const getters = {

	hasUser: (state) => (id) => {
		return state.users?.id !== undefined;
	},
	getUser: (state) => (id) => {
		return state.users?.id;
	},

};

const mutations = {

	setUser(state, user) {
		state.users[user.id] = user;
	},

	setUsers(state, data) {
		for (let i in data) {
			state.users[data.id] = data;
		}
	},

	clearUser(state, id) {
		if (state.users[id]) {
			delete state.users[id];
		}
	}

};

const actions = {

	/*
	getUser: async ({commit, getters, dispatch}, user_id) => {

		if (getters.hasUser(user_id)) {

			const user = getters.getUser(user_id);

			if (typeof user?.then === 'function') {
				// Probably a promise
				return user;
			} else {
				return new Promise((resolve, reject) => {
					resolve(user);
				});
			}

		} else {

			return axios
				.get(api_v2_user_get(user_id), {})
				.then(({data}) => {

					if (data) {
						commit('setUser', data);
						return getters.getUser(user_id);
					} else {
						throw ('No data retured from server');
					}

				})
				.catch((response) => {
					throw convertErrorResponseToMessage(response);
				});

		}

	},
	 */

	search: async ({commit, getters, dispatch}, {search, limit}) => {

		let data = {s: search};

		if (limit) {
			data.limit = limit;
		}

		return axios
			.get(
				api_v2_users_find,
				{params: data}
			)
			.then(({data}) => {

				if (data && data.length > 0) {
					commit('setUsers', data);
				}

				return data;

			})
			.catch((response) => {
				throw convertErrorResponseToMessage(response);
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