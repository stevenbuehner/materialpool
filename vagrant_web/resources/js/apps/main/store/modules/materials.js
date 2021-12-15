import {
	api_v1_materials_copy,
	api_v1_materials_create_download,
	api_v1_materials_show,
	api_v1_materials_store,
	api_v1_materials_update,
	api_v2_materialresource_attach,
	api_v2_materialresource_detach,
	api_v2_materials_delete
}                                      from '../../../../components/serverRoutes'
import axios                           from '../../axiosInstance';
import {convertErrorResponseToMessage} from "./handleErrorsHelper";


const state = {
	materials: {}, // Hier werden die Materials gespeichert. Alle. Ob nur grob oder via Suche.
	// Wenn das Material detailiert (also einzeln) geladen wurde, wird zusätzlich ein Flag bei
	// state.materialDetailsLoaded auf true gesetzt
	// Da nur diese VueX-Datei die Detailierte Informationen lädt, wird das Flag nur gesetzt
	// wenn hier direkt ein einzelnes Material geladen wurde.
	materialDetailsLoaded: {}, // IDs der Materialien, von denen Details bekannt sind
	loadingMaterialDetailsPromises: {}
};

const getters = {

	hasMaterialPreview: (state) => (id) => {
		return state.materials.hasOwnProperty(id);
	},

	hasMaterialDetails: (state) => (id) => {
		return state.materialDetailsLoaded.hasOwnProperty(id);
	},

	getMaterial: (state) => (id) => {
		if (state.materials[id]) {
			return state.materials[id];
		}

		return null;
	},

	/**
	 * Gibt das Promise zurück oder FALSE, falls kein Promise existiert
	 * @param state
	 * @returns {function(*): (*|boolean)}
	 */
	getMaterialLoadingPromise: (state) => (id) => {

		if (state.loadingMaterialDetailsPromises.hasOwnProperty(id)) {

			return state.loadingMaterialDetailsPromises[id];

			/*
		} else if (state.materials.hasOwnProperty(id)) {

			state.loadingMaterialDetailsPromises[id] = new Promise(function (resolve, reject) {
				resolve(state.materials[id]);
			});

			return state.loadingMaterialDetailsPromises[id];
			 */

		} else {

			return false;

		}

	}
};

const mutations = {
	setMaterial(state, material) {
		state.materials[material.id] = material;
	},

	setMaterialDetailsLoaded(state, {id, loaded = true}) {
		if (loaded) {
			state.materialDetailsLoaded[id] = true;
		} else if (state.materialDetailsLoaded.hasOwnProperty(id)) {
			delete state.materialDetailsLoaded[id];
		}
	},

	setMaterialLoadingPromise(state, {id, promise}) {
		state.loadingMaterialDetailsPromises[id] = promise;
	},

	clearMaterialLoadingPromise(state, id) {
		delete state.loadingMaterialDetailsPromises[id];
	},

	clearMaterial(state, id) {
		delete state.materials[id];
		delete state.loadingMaterialDetailsPromises[id];
		delete state.materialDetailsLoaded[id];
	}
};

const actions = {
	hasMaterialPreviewCached: ({getters}, id) => {
		return getters.hasMaterialPreview(id);
	},

	hasMaterialDetailsCached: ({getters}, id) => {
		return getters.hasMaterialDetails(id);
	},

	/**
	 * Gibt detailiertes oder Preview-Material als Promise zurück ... je nach dem, was da ist ...
	 *
	 * @param getters
	 * @param dispatch
	 * @param id
	 * @returns {Promise<unknown>|(function(*): (*|boolean))}
	 */
	getMaterial: ({getters, dispatch}, id) => {

		if (getters.hasMaterialPreview(id)) {
			const mat = getters.getMaterial(id);
			return new Promise((resolve, reject) => {
				resolve(mat);
			});
		} else {
			return dispatch('getMaterialDetailed', id);
		}

	},

	/**
	 * Gibt ein detailiertes Material zurück
	 * @param commit
	 * @param dispatch
	 * @param getters
	 * @param id
	 * @returns {Promise<unknown>|(function(*): (*|boolean))}
	 */
	getMaterialDetailed: ({commit, dispatch, getters}, id) => {

		const loadingPromise = getters.getMaterialLoadingPromise(id);

		if (getters.hasMaterialDetails(id)) {
			// Das Material wurde bereits detailiert geladen
			return new Promise((resolve, reject) => {
				const mat = getters.getMaterial(id);
				resolve(mat);
			});
		} else if (loadingPromise && typeof loadingPromise.then === 'function') {
			// Es gibt bereits ein Promise für MaterialDetails
			return loadingPromise;
		} else {
			// Es gibt weder ein detailiertes Material im Cache noch ein passendes Promise
			// => Material neu laden, Promise abspeichern und zurückgeben

			const newLoadingPromise = new Promise((resolve, reject) => {

				axios.get(api_v1_materials_show(id), {})
				     .then((response) => {
					     dispatch('setMaterialDetailed', response.data);
					     // commit('clearMaterialLoadingPromise', id); // Wird mit setMaterialDetailed bereits gemacht ... gehört der Vollständigkeit halberaber trotzdem hier hin ...
					     resolve(getters.getMaterial(id));
				     })
				     .catch((response) => {
					     commit('clearMaterialLoadingPromise', id);
					     reject(convertErrorResponseToMessage(response));
				     });

			});

			commit('setMaterialLoadingPromise', {id: id, promise: newLoadingPromise});

			return newLoadingPromise;
		}

	},

	/**
	 * Cache Material (als Preview)
	 * @param commit
	 * @param dispatch
	 * @param material
	 */
	setMaterial: ({commit, dispatch}, material) => {
		dispatch('clearMaterial', material.id);
		commit('setMaterial', material);
	},

	/**
	 * Cache Material (detailiert)
	 * @param commit
	 * @param dispatch
	 * @param material
	 */
	setMaterialDetailed: ({commit, dispatch}, material) => {
		dispatch('clearMaterial', material.id);
		commit('setMaterial', material);
		commit('setMaterialDetailsLoaded', {id: material.id});
	},


	/**
	 * Erstelle Material und Cache es (detailierte Version)
	 * @param commit
	 * @param dispatch
	 * @param title
	 * @param from_bot
	 * @param description
	 * @param rating
	 * @param author
	 * @param keywords
	 * @param bibleverses
	 * @returns {Promise<AxiosResponse<any> | void>}
	 */
	create: ({commit, dispatch}, {title, from_bot, description, rating, author, keywords, bibleverses}) => {

		let data = {
			title
		};

		if (from_bot === true || from_bot === false) {
			data.from_bot = from_bot;
		}

		if (description) {
			data.description = description;
		}

		if (rating) {
			data.rating = parseInt(rating);
		}

		if (author) {
			data.author = author;
		}

		if (keywords) {
			data.keywords = keywords;
		}

		if (bibleverses) {
			data.bibleverses = bibleverses;
		}

		const result = axios.post(api_v1_materials_store, data)
		                    .then((result) => result.data)
		                    .catch((response) => {
			                    throw convertErrorResponseToMessage(response)
		                    });

		result.then((material) => {
			commit('setMaterialDetailed', material);
		});

		return result;
	},


	/**
	 * Speichere Änderungen im Material und Cache sie (detailiert)
	 * @param commit
	 * @param getters
	 * @param dispatch
	 * @param id
	 * @param data
	 * @returns {Promise<AxiosResponse<any>>}
	 */
	updateMaterial: ({commit, getters, dispatch}, {id, data}) => {

		data._method = 'PUT';

		const result = axios.post(api_v1_materials_update(id), data);

		result.then((response) => {

			dispatch('setMaterialDetailed', response.data);
			return getters.getMaterial(id);

		}).catch((response) => {
			throw convertErrorResponseToMessage(response)
		});

		return result;
	},

	/**
	 * Aktualisiere ein Keyword bei einem Material
	 * @param commit
	 * @param getters
	 * @param dispatch
	 * @param materialId
	 * @param keyword
	 * @param pivot
	 */
	updateMaterialKeywords: ({commit, getters, dispatch}, {materialId, keyword, pivot}) => {

		let mat = getters.getMaterial(materialId);
		// console.info('Received Update request');

		if (mat && mat.keywords) {

			let found = mat.keywords.find(kw => kw.id === keyword.id);

			if (!found) {
				found = keyword;
				mat.keywords.push(found);
			} else {

				for (let prop in keyword) {
					found[prop] = keyword[prop];
				}

			}

			// Update pivot
			if (pivot) {
				found.pivot = pivot;
			}

			// Ob das Material preview oder detailier ist, bleibt beim Setter unberührt
			commit('setMaterial', mat);
		}

	},

	addKeywordToMaterial: ({commit, getters, dispatch}, {materialId, keyword, relevance}) => {

		let mat = getters.getMaterial(materialId);

		if (mat) {
			const found = mat.keywords.find(el => el.id === keyword.id);

			if (found === undefined) {
				keyword.pivot = {relevance: relevance}
				mat.keywords.push(keyword);
				commit('setMaterial', mat);
			} else {
				dispatch('updateMaterialKeywords', {materialId, keyword, pivot: {relevance}});
			}
		}

	},

	removeKeywordFromMaterial: ({commit, getters, dispatch}, {materialId, keywordId}) => {

		let mat = getters.getMaterial(materialId);

		if (mat && mat.keywords) {
			mat.keywords = mat.keywords.filter((el) => {
				return el.id !== keywordId
			});

			commit('setMaterial', mat);
		}
	},


	clearMaterial: ({commit, getters, dispatch}, id) => {
		commit('clearMaterial', id);
	},

	attachResource: ({commit, getters, dispatch}, {materialId, resourceId, limitation}) => {

		const url = api_v2_materialresource_attach(materialId, resourceId);
		let data  = {};

		if (limitation && limitation.type && limitation.value) {
			data = {
				limitation: {
					type: limitation.type,
					value: limitation.value
				}
			}
		}

		const result = axios.post(url, data)
		                    .then((result) => result.data)
		                    .catch((response) => {
			                    throw convertErrorResponseToMessage(response)
		                    });

		// Update material-Cache (detailiert)
		result.then(({material}) => {
			if (material) {
				dispatch('setMaterialDetailed', material);
			}
		});

		// Update resource-cache
		result.then(({resource}) => {
			if (resource) {
				dispatch('resources/setResource', resource, {root: true});
			}
		});

		return result;
	},

	detachResource: ({commit, getters, dispatch}, {materialId, resourceId}) => {

		const url = api_v2_materialresource_detach(materialId, resourceId);

		const result = axios.delete(url)
		                    .then((result) => result.data)
		                    .catch((response) => {
			                    throw convertErrorResponseToMessage(response)
		                    });

		// ALWAYS (!): Update material-Cache (detailiert)
		result.then(({material}) => {
			if (material) {
				dispatch('setMaterialDetailed', material);
			}
		});

		// ALWAYS (!): Update resource-cache
		result.then(({resource}) => {
			if (resource) {
				dispatch('resources/setResource', resource, {root: true});
			}
		});

		return result;
	},

	deleteMaterial: ({commit, dispatch}, id) => {

		return axios
			.delete(api_v2_materials_delete(id)).then(({data}) => {

				commit('clearMaterial', id);

				return (data.success && data.success === true);

			})
			.catch((response) => {
				throw convertErrorResponseToMessage(response)
			});

	},

	copyMaterial: ({commit, dispatch}, id) => {
		return axios
			.get(api_v1_materials_copy(id))
			.then(({data}) => {
				const material = data;

				dispatch('setMaterialDetailed', material);

				if (material.resources && Array.isArray(material.resources)) {
					material.resources.forEach((el) => {
						dispatch('resources/clearResource', el.id, {root: true});
					});
				}

				return material;
			})
			.catch((response) => {
				throw convertErrorResponseToMessage(response)
			});
	},

	createDownloadLink: ({commit, dispatch}, id) => {
		return axios
			.get(api_v1_materials_create_download(id)).then(({data}) => {
				if (data.success === false) {
					throw('invalid download link');
				} else {
					return {
						link: data.link,
						until: data.until
					};
				}
			})
			.catch((response) => {
				throw convertErrorResponseToMessage(response)
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