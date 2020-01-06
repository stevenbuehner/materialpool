import {
	api_v1_resources_create_material,
	api_v1_resources_delete,
	api_v1_resources_find,
	api_v1_resources_show,
	api_v1_resources_store,
	api_v1_resources_update
}                                      from '../../../../components/serverRoutes'
import axios                           from '../../axiosInstance';
import {convertErrorResponseToMessage} from "./handleErrorsHelper";


const state = {
	resources: {},
	loadingPromise: {}
};

const getters   = {
	updateResource: (state) => (id) => {
		if (state.resources[id]) {
			return state.resources[id];
		}

		return null;
	},

	getResourceLoadingPromise: (state) => (id) => {
		if (state.loadingPromise[id]) {
			return state.loadingPromise[id];
		} else if (state.resources[id]) {
			return state.loadingPromise[id] = new Promise(function (resolve, reject) {
				resolve(state.resources[id]);
			});
		} else {
			return false;
		}
	}
};
const mutations = {
	setResource(state, resource) {
		state.resources[resource.id] = resource;
	},

	setResourceLoadingPromise(state, {id, promise}) {
		state.loadingPromise[id] = promise;
	},

	clearResource(state, id) {
		delete state.resources[id];
		delete state.loadingPromise[id];
	}
};

const actions = {
	get: ({getters, commit, dispatch, state}, id) => {

		let loadingPromise = getters.getResourceLoadingPromise(id);

		if (loadingPromise === false) {
			loadingPromise = new Promise((resolve, reject) => {

				let res = getters.updateResource(id);

				if (res) {
					resolve(res);
				} else {
					axios
						.get(api_v1_resources_show(id), {
							params: {
								relations: ['materials', 'materials.keywords', 'materials.bibleverses', 'creator']
							}
						})
						.then((response) => {
							dispatch('setResource', response.data);
							resolve(getters.updateResource(id));
						})
						.catch((response) => throw convertErrorResponseToMessage(response));
				}
			});

			commit('setResourceLoadingPromise', {id: id, promise: loadingPromise});

		} else {
			loadingPromise = loadingPromise;
		}

		return loadingPromise;

	},

	createTextResource: ({commit}, {text, notes, is_public}) => {

		notes     = notes || '';
		is_public = is_public || false;

		return axios
			.post(api_v1_resources_store, {
				content: text,
				notes,
				is_public
			})
			.then(({data}) => {

				if (data.id) {
					// commit('clearResource', id); // Resource should not exist yet
					commit('setResource', data);
				}

				return data;
			})
			.catch((response) => throw convertErrorResponseToMessage(response));

	},

	deleteResource: ({commit}, id) => {

		return axios.delete(api_v1_resources_delete(id))
		            .then(({data}) => {

			            commit('clearResource', id);

			            return (data.success && data.success === true);

		            })
		            .catch((response) => throw convertErrorResponseToMessage(response));

	},

	setResource: ({commit}, resource) => {
		commit('clearResource', resource.id);
		commit('setResource', resource);
	},

	update: ({commit, dispatch}, {id, data}) => {
		return axios.put(api_v1_resources_update(id), data)
		            .then((response) => response.data)
		            .then((resource) => {
			            dispatch('setResource', resource);
			            return resource;
		            })
		            .catch((response) => {
			            throw convertErrorResponseToMessage(response);
		            });
	},

	clearResource: ({commit}, id) => {
		commit('clearResource', id);
	},

	/**
	 *
	 * @param commit
	 * @param dispatch
	 * @param resourceIds
	 * @param meta
	 * @param from_bot
	 * @return {Promise<material>}
	 */
	autoCreateMaterial: ({commit, dispatch}, {resourceIds, meta, from_bot}) => {

		meta     = meta || '';
		from_bot = from_bot || false;

		const promise = axios
			.post(api_v1_resources_create_material, {
				resourceIds: resourceIds,
				from_bot,
				meta
			})
			.then(({data}) => {
				return data;
			})
			.catch((response) => throw convertErrorResponseToMessage(response));

		promise.then((material) => {

			for (let i in material.resources) {
				// Clear, because the now assigned material is missing in the resource data
				dispatch('clearResource', material.resources[i]);
			}

			dispatch('materials/setMaterial', material, {root: true});
		});

		return promise;
	},

	find: ({dispatch}, {id, remote_path, is_public, content_hash, missing_materials, ignore_ids, order_by, order_dir, page}) => {

		let searchQuery = {};

		if (id) {
			searchQuery.id = id;
		}

		if (remote_path) {
			searchQuery.remote_path = remote_path;
		}

		if (is_public === true || is_public === false) {
			searchQuery.is_public = is_public;
		}

		if (content_hash) {
			searchQuery.content_hash = content_hash;
		}

		if (missing_materials === true) {
			searchQuery.missing_materials = true;
		}

		if (ignore_ids) {
			searchQuery.ignore_ids = ignore_ids;
		}

		if (order_by) {
			searchQuery.order_by = order_by;
		}

		if (order_dir) {
			searchQuery.order_dir = order_dir;
		}

		if (page) {
			searchQuery.page = page;
		}

		return axios
			.get(api_v1_resources_find, {params: searchQuery})
			.then(({data}) => {
				for (let i in data.data) {
					dispatch('setResource', data.data[i]);
				}
				return data;
			})
			.catch((response) => {
				throw convertErrorResponseToMessage(response);
			});

	},

	lonely: ({dispatch}, {page}) => {
		return dispatch('find', {missing_materials: true, page});
	}

};

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations
};