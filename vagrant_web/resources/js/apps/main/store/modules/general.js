import {
	api_v1_general_options,
	api_v1_users_store_settings,
	api_v1_users_view
}                                                                              from '../../../../components/serverRoutes';
import axios                                                                   from '../../axiosInstance';
import {convertErrorResponseToMessage}                                         from "./handleErrorsHelper";
import {extractValueById, overrideValueById, removeValueById, verifyStructure} from "../helper/idExplode";

const USER_SETTINGS_ID = 'frontend_user_settings';


const state = {
	options: null,
	users: {},
};

const getters = {

	getOptions: (state) => () => {
		return state.options;
	},

	getUser: (state) => (id) => {
		return state.users[id] || null;
	}

};

const mutations = {

	setOptions(state, options) {
		state.options = options;
	},

	clearOptions(state) {
		state.options = null;
	},

	setUser(state, data) {
		state.users[data.id] = data;

		// Aktualisiere auch den CurrentUser, wenn es sich um den selbigen handelt
		if (state.options.user && state.options.user.id === data.id) {
			state.options.user = data;
			// console.log('Options-User mitaktualisiert ...');
		}

	},

	setUserPromise(state, {promise, id}) {
		state.users[id] = promise;
	},

	clearUser(state, id) {
		delete state.users[id];
	},

};

const actions = {

	options: async ({getters, commit}) => {

		const opt = getters.getOptions();

		if (opt === null) {
			// Nur das erste Mal ein Promise zwischenspeichern und ausgeben, bis die Daten da sind
			const promise = axios.get(api_v1_general_options)
			                     .then(({data}) => {
				                     return data;
			                     })
			                     .catch((result) => {
				                     return convertErrorResponseToMessage(result);
			                     });

			commit('setOptions', promise);

			// Wenn die Daten da sind, speichere diese statt dem Promise ab
			promise.then((data) => {
				commit('setOptions', data);
				commit('setUser', data.user);
			});

			return promise;

		} else if (typeof opt.then === "function") {
			// Wenn noch ein Promise gespeichert ist, gebe das Promise zurück
			return opt;
		} else {
			// Wir haben schon Daten da (!= NULL), gebe diese zurück
			return opt;
		}

	},

	maxUploadSize: ({dispatch}) => {
		return dispatch('options').then((allOptions) => {
			return allOptions?.server?.max_upload;
		});
	},

	currentUser: ({dispatch}) => {
		return dispatch('options').then((allOptions) => {
			return allOptions?.user;
		});
	},

	currentUserId: async ({dispatch}) => {
		const currentUser = await dispatch('currentUser');
		return currentUser?.id;
	},

	getUser: async ({dispatch, getters, commit}, id) => {

		const user = getters.getUser(id);

		if (user === null) {

			const promise = axios.get(api_v1_users_view(id))
			                     .then(({data}) => data)
			                     .catch((result) => {
				                     return convertErrorResponseToMessage(result);
			                     });

			commit('setUserPromise', {promise, id});

			promise.then((user) => {
				commit('setUser', user);
			});

			return promise;

		} else if (typeof user.then === "function") {

			// user ist ein Promise, weil er noch geladen wird
			return user;

		} else {
			// User besteht aus Daten (!== null)
			return user;
		}

	},

	isAdmin: ({dispatch}) => {
		return dispatch('currentUser').then(({is_admin}) => {
			return is_admin || false;
		});

	},

	systemName: ({dispatch}) => {
		return dispatch('options').then((allOptions) => {
			return allOptions.systemname || 'MaterialPool Default';
		});
	},

	/**
	 * Gibt die Einstellungen des aktuell verwendeten Benutzers zurück
	 * @param dispatch
	 * @param settingId Eine ID der Einstellungen (Bsp.: mattemplate.default - das wird aufgelöst in ein verschachteltes Array)
	 * @param defaultValue
	 * @returns {*}
	 */
	currentUserSetting: ({dispatch}, {settingId, defaultValue}) => {

		return dispatch('currentUser')
			.then((currentUser) => {
				return verifyStructure(currentUser[USER_SETTINGS_ID]);
			}).then((allSettings) => {
				return extractValueById(settingId, allSettings, defaultValue);
			});

	},

	getUserSetting: ({dispatch}, {userId, settingId, defaultValue}) => {
		return dispatch('getUser', userId)
			.then((user) => {
				return verifyStructure(user[USER_SETTINGS_ID]);
			}).then((allSettings) => {
				return extractValueById(settingId, allSettings, defaultValue);
			});
	},

	/**
	 * Schickt das Gesamte (!) Settings-Paket (Objekt) zum Server uns speichert es
	 * @param dispatch
	 * @param commit
	 * @param userId
	 * @param allSettings
	 * @returns {Promise<T | string>}
	 */
	storeCompleteUserSettings: ({dispatch, commit}, {userId, allSettings}) => {

		// const clearedSettings = cleanupEmptyNodes(allSettings);

		return axios.post(api_v1_users_store_settings(userId), {data: allSettings})
		            .then(({data}) => data)
		            .then((data) => {
			            commit('setUser', data);
			            return data;
		            })
		            .catch((result) => {
			            return convertErrorResponseToMessage(result);
		            });
	},

	/**
	 * Speichert einen bestimmten Teil der Einstellungen (settingsID)
	 * @param dispatch
	 * @param commit
	 * @param userId
	 * @param settingId
	 * @param data
	 * @returns {Promise<*>}
	 */
	storeUserSetting: async ({dispatch, commit}, {userId, settingId, data}) => {

		const allUserSettings = await dispatch('getUserSetting', {userId});
		const newUserSettings = overrideValueById(settingId, allUserSettings, data);

		return dispatch('storeCompleteUserSettings', {userId, allSettings: newUserSettings})

	},

	storeCurrentUserSetting: async ({dispatch, commit}, {settingId, data}) => {

		const user = await dispatch('currentUser');

		return dispatch('storeUserSetting', {
			userId: user.id,
			settingId,
			data
		})

	},

	/**
	 * Entfernt alle Daten der UserSettings unterhalb des angegeben ID-Pfads
	 * @param dispatch
	 * @param commit
	 * @param userId
	 * @param settingId
	 * @returns {Promise<*>}
	 */
	removeUserSetting: async ({dispatch, commit}, {userId, settingId}) => {

		const allUserSettings = await dispatch('getUserSetting', {userId});
		const newUserSettings = removeValueById(settingId, allUserSettings);

		return dispatch('storeCompleteUserSettings', {userId, allSettings: newUserSettings})

	},

	removeCurrentUserSetting: async ({dispatch, commit}, settingId) => {

		const user = await dispatch('currentUser');

		return dispatch('removeUserSetting', {userId: user.id, settingId});

	},


};

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations
};