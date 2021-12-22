import axios                           from '../../axiosInstance';
import {convertErrorResponseToMessage} from "./handleErrorsHelper";
import {
	api_v2_materialusage_delete,
	api_v2_materialusage_index,
	api_v2_materialusage_store,
	api_v2_materialusage_update
}                                      from "../../../../components/serverRoutes";
import moment                          from 'moment';


const state = {
	// usages_by_usageid: {},
	usages_by_materialid: {}
};

const getters = {

	/**
	 * Has MaterialUsage as resolved Object {} or as Promise waiting, for resolution
	 * @param state
	 * @returns {function(*=): boolean}
	 */
	hasMaterialUsages: (state) => (material_id) => {
		return state.usages_by_materialid.hasOwnProperty(material_id);
	},

	/*
	getMaterialUsage: (state) => (material_id) => {
		return state.materialDetailsLoaded.hasOwnProperty(material_id);
	},
	 */

	/**
	 * Get MaterialUsages as resolved Object {}or as Promise, waiting for resolution
	 * @param state
	 * @returns {(function(*=): (*|null))|*}
	 */
	getMaterialUsages: (state) => (material_id) => {
		if (state.usages_by_materialid.hasOwnProperty(material_id)) {
			return state.usages_by_materialid[material_id];
		}

		return null;
	},

};

const mutations = {

	setMaterialUsages(state, usages) {

		if (usages instanceof Array && usages.length > 0) {
			state.usages_by_materialid[usages[0].material_id] = usages;
		} else {
			console.error('setMaterialUsages with empty array');
		}

	},

	setMaterialUsage: function (state, usage) {
		if (usage.hasOwnProperty('material_id')) {

			const matId = usage.material_id;

			if (!state.usages_by_materialid.hasOwnProperty(matId) || !state.usages_by_materialid[matId] instanceof Array) {
				state.usages_by_materialid[matId] = [];
			}

			const found = state.usages_by_materialid[matId].findIndex((el) => el.id === usage.id);

			if (found !== -1) {
				state.usages_by_materialid[matId][found] = usage;
			} else {
				state.usages_by_materialid[matId].push(usage);
			}

		}

	},

	clearMaterialUsages(state, material_id) {
		delete state.usages_by_materialid[material_id];
	},

	clearMaterialUsage(state, {material_id, id}) {
		if (state.usages_by_materialid.hasOwnProperty(material_id) && state.usages_by_materialid[material_id] instanceof Array) {
			state.usages_by_materialid[material_id] = state.usages_by_materialid[material_id].filter((el) => {
				return el.id !== id;
			});
		}
	}
};

const actions = {
	hasMaterialUsagesCached: ({getters}, material_id) => {
		return getters.hasMaterialUsages(material_id);
	},

	getMaterialUsages: ({getters, dispatch, commit}, material_id) => {

		if (getters.hasMaterialUsages(material_id)) {

			const mat = getters.getMaterialUsages(material_id);

			if (typeof mat?.then === 'function') {
				// Probably a promise
				return mat;
			} else {
				return new Promise((resolve, reject) => {
					resolve(mat);
				});
			}

		} else {

			return axios
				.get(api_v2_materialusage_index(material_id), {})
				.then(({data}) => {

					if (data.length > 0) {
						commit('setMaterialUsages', data);
						return getters.getMaterialUsages(material_id);
					} else {
						commit('clearMaterialUsages', material_id);
						return [];
					}

					// commit('clearMaterialLoadingPromise', id); // Wird mit setMaterialDetailed bereits gemacht ... gehört der Vollständigkeit halberaber trotzdem hier hin ...
				})
				.catch((response) => {
					throw convertErrorResponseToMessage(response);
				});

		}

	},

	addMaterialUsage: ({getters, dispatch, commit}, {material_id, used_by_id}) => {

		return dispatch('updateMaterialUsage', {material_id, used_by_id});

	},

	updateMaterialUsage: ({getters, dispatch, commit}, {material_id, id, datetime, place, reason, used_by_id}) => {

		const url = id === undefined ? api_v2_materialusage_store(material_id) : api_v2_materialusage_update(material_id, id);

		if (datetime === undefined) {
			datetime = new Date();
		}

		if (place === undefined) {
			place = '';
		}

		if (reason === undefined) {
			reason = '';
		}

		if (used_by_id === undefined) {
			used_by_id = null;
		}

		return axios
			.post(url, {
				datetime: moment(datetime).format(),
				place,
				reason,
				used_by_id
			})
			.then(({data}) => {
				commit('setMaterialUsage', data);

				return data;
			})
			.catch((response) => {
				throw convertErrorResponseToMessage(response);
			});

	},

	deleteMaterialUsage: ({getters, dispatch, commit}, {material_id, id}) => {

		const url  = api_v2_materialusage_delete(material_id, id);
		const data = {
			_method: 'DELETE'
		};

		return axios
			.post(url, data)
			.then((response) => {

				if (response?.data?.success === true) {
					commit('clearMaterialUsage', {material_id, id});
				} else {
					throw convertErrorResponseToMessage(response);
				}

				return true;

			})
			.catch((response) => {
				throw convertErrorResponseToMessage(response);
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