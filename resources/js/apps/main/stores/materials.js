import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {convertErrorResponseToMessage} from '../store/modules/handleErrorsHelper';
import {
	api_v1_materials_copy,
	api_v1_materials_create_download,
	api_v1_materials_download_status,
	api_v1_materials_show,
	api_v1_materials_store,
	api_v1_materials_update,
	api_v1_materials_user_ranking,
	api_v2_materialresource_attach,
	api_v2_materialresource_detach,
	api_v2_materials_delete,
} from '../../../components/serverRoutes';
import {useResourcesStore} from './resources';

export const useMaterialsStore = defineStore('materials', {
	state: () => ({
		materials: {},
		materialDetailsLoaded: {},
		loadingMaterialDetailsPromises: {},
	}),

	getters: {
		hasMaterialPreview: state => id => Object.hasOwn(state.materials, id),
		hasMaterialDetails: state => id => Object.hasOwn(state.materialDetailsLoaded, id),
		getMaterial: state => id => state.materials[id] || null,
		getMaterialLoadingPromise: state => id => state.loadingMaterialDetailsPromises[id] || false,
	},

	actions: {
		hasMaterialPreviewCached(id) {
			return this.hasMaterialPreview(id);
		},

		hasMaterialDetailsCached(id) {
			return this.hasMaterialDetails(id);
		},

		getMaterialById(id) {
			return this.hasMaterialPreview(id)
				? Promise.resolve(this.getMaterial(id))
				: this.getMaterialDetailed(id);
		},

		getMaterialDetailed(id) {
			if (this.hasMaterialDetails(id)) {
				return Promise.resolve(this.getMaterial(id));
			}

			const loadingPromise = this.getMaterialLoadingPromise(id);
			if (loadingPromise && typeof loadingPromise.then === 'function') {
				return loadingPromise;
			}

			const request = axios.get(api_v1_materials_show(id), {})
				.then(({data}) => {
					this.setMaterialDetailed(data);
					return this.getMaterial(id);
				})
				.catch(response => {
					delete this.loadingMaterialDetailsPromises[id];
					throw convertErrorResponseToMessage(response);
				});

			this.loadingMaterialDetailsPromises[id] = request;
			return request;
		},

		setMaterial(material) {
			this.clearMaterial(material.id);
			this.materials[material.id] = material;
		},

		setMaterialDetailed(material) {
			this.clearMaterial(material.id);
			this.materials[material.id] = material;
			this.materialDetailsLoaded[material.id] = true;
		},

		async create({title, from_bot, description, rating, author, keywords, bibleverses}) {
			const data = {title};

			if (from_bot === true || from_bot === false) data.from_bot = from_bot;
			if (description) data.description = description;
			if (rating) data.rating = parseInt(rating);
			if (author) data.author = author;
			if (keywords) data.keywords = keywords;
			if (bibleverses) data.bibleverses = bibleverses;

			try {
				const {data: material} = await axios.post(api_v1_materials_store, data);
				this.setMaterialDetailed(material);
				return material;
			} catch (response) {
				throw convertErrorResponseToMessage(response);
			}
		},

		updateMaterial({id, data}) {
			data._method = 'PUT';
			const request = axios.post(api_v1_materials_update(id), data);
			request.then(({data: material}) => this.setMaterialDetailed(material));
			return request;
		},

		updateUserRanking({materialId, rating}) {
			return axios.put(api_v1_materials_user_ranking(materialId), {rating})
				.then(({data}) => {
					this.applyUserRanking(materialId, data);
					return data;
				})
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},

		removeUserRanking(materialId) {
			return axios.delete(api_v1_materials_user_ranking(materialId))
				.then(({data}) => {
					this.applyUserRanking(materialId, data);
					return data;
				})
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},

		applyUserRanking(materialId, ranking) {
			const material = this.getMaterial(materialId);
			if (!material) return;

			Object.assign(material, ranking);
			this.materials[materialId] = material;
		},

		updateMaterialKeywords({materialId, keyword, pivot}) {
			const material = this.getMaterial(materialId);
			if (!material?.keywords) return;

			let found = material.keywords.find(entry => entry.id === keyword.id);
			if (!found) {
				found = keyword;
				material.keywords.push(found);
			} else {
				Object.assign(found, keyword);
			}

			if (pivot) found.pivot = pivot;
			this.materials[material.id] = material;
		},

		addKeywordToMaterial({materialId, keyword, relevance}) {
			const material = this.getMaterial(materialId);
			if (!material) return;

			const found = material.keywords.find(entry => entry.id === keyword.id);
			if (found === undefined) {
				keyword.pivot = {relevance};
				material.keywords.push(keyword);
				this.materials[material.id] = material;
			} else {
				this.updateMaterialKeywords({materialId, keyword, pivot: {relevance}});
			}
		},

		removeKeywordFromMaterial({materialId, keywordId}) {
			const material = this.getMaterial(materialId);
			if (material?.keywords) {
				material.keywords = material.keywords.filter(entry => entry.id !== keywordId);
				this.materials[material.id] = material;
			}
		},

		clearMaterial(id) {
			delete this.materials[id];
			delete this.loadingMaterialDetailsPromises[id];
			delete this.materialDetailsLoaded[id];
		},

		attachResource({materialId, resourceId, limitation}) {
			let data = {};
			if (limitation?.type && limitation?.value) {
				data = {limitation: {type: limitation.type, value: limitation.value}};
			}

			const request = axios.post(api_v2_materialresource_attach(materialId, resourceId), data)
				.then(({data: response}) => response)
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
			request.then(({material, resource}) => {
				if (material) this.setMaterialDetailed(material);
				if (resource) useResourcesStore().setResource(resource);
			});
			return request;
		},

		detachResource({materialId, resourceId}) {
			const request = axios.delete(api_v2_materialresource_detach(materialId, resourceId))
				.then(({data}) => data)
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
			request.then(({material, resource}) => {
				if (material) this.setMaterialDetailed(material);
				if (resource) useResourcesStore().setResource(resource);
			});
			return request;
		},

		deleteMaterial(id) {
			return axios.delete(api_v2_materials_delete(id))
				.then(({data}) => {
					this.clearMaterial(id);
					return data.success === true;
				})
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},

		copyMaterial(id) {
			return axios.get(api_v1_materials_copy(id))
				.then(({data: material}) => {
					this.setMaterialDetailed(material);
					for (const resource of material.resources || []) {
						useResourcesStore().clearResource(resource.id);
					}
					return material;
				})
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},

		createDownloadLink(id) {
			return axios.get(api_v1_materials_create_download(id))
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				})
				.then(({data}) => {
					if (data.success === false) throw 'invalid download link';
					return {status: data.status, statusUrl: data.status_url, until: data.until};
				});
		},

		getDownloadStatus(id, token) {
			return axios.get(api_v1_materials_download_status(id, token))
				.then(({data}) => data)
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},
	},
});
