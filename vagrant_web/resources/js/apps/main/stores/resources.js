import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {queue} from '../store/networkQueue';
import {convertErrorResponseToMessage} from '../store/modules/handleErrorsHelper';
import {
	api_v1_resources_create_material,
	api_v1_resources_delete,
	api_v1_resources_find,
	api_v1_resources_replace_with,
	api_v1_resources_show,
	api_v1_resources_store,
	api_v1_resources_update,
} from '../../../components/serverRoutes';
import {useMaterialsStore} from './materials';

export const useResourcesStore = defineStore('resources', {
	state: () => ({resources: {}, loadingPromise: {}}),

	getters: {
		updateResource: state => id => state.resources[id] || null,
		getResourceLoadingPromise: state => id => state.loadingPromise[id] || false,
	},

	actions: {
		get(id) {
			const pending = this.getResourceLoadingPromise(id);
			if (pending) return pending;

			const cached = this.updateResource(id);
			const request = cached ? Promise.resolve(cached) : axios.get(api_v1_resources_show(id), {
				params: {relations: ['materials', 'materials.keywords', 'materials.bibleverses', 'creator']},
			})
				.then(({data}) => {
					this.setResource(data);
					return this.updateResource(id);
				})
				.catch(response => {
					delete this.loadingPromise[id];
					throw convertErrorResponseToMessage(response);
				});

			this.loadingPromise[id] = request;
			return request;
		},

		async getMultiple(resourceIds) {
			const resources = await Promise.all(resourceIds.map(id => queue.add(() => this.get(id))));
			this.setMultipleResources(resources);
			return resources;
		},

		async createTextResource({text, notes = '', is_public = false}) {
			try {
				const {data} = await axios.post(api_v1_resources_store, {content: text, notes, is_public});
				if (data.id) this.resources[data.id] = data;
				return data;
			} catch (response) {
				throw convertErrorResponseToMessage(response);
			}
		},

		deleteResource(id) {
			return axios.delete(api_v1_resources_delete(id))
				.then(({data}) => {
					this.clearResource(id);
					return data.success === true;
				})
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},

		setResource(resource) {
			this.clearResource(resource.id);
			this.resources[resource.id] = resource;
		},

		setMultipleResources(resources) {
			for (const resource of resources) this.resources[resource.id] = resource;
		},

		update({id, data}) {
			return axios.put(api_v1_resources_update(id), data)
				.then(({data: resource}) => {
					this.setResource(resource);
					return resource;
				})
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},

		clearResource(id) {
			delete this.resources[id];
			delete this.loadingPromise[id];
		},

		autoCreateMaterial({resourceIds, meta = '', from_bot = false}) {
			const request = axios.post(api_v1_resources_create_material, {resourceIds, from_bot, meta})
				.then(({data}) => data)
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
			request.then(material => {
				for (const resource of material.resources || []) {
					this.clearResource(resource.id ?? resource);
				}
				useMaterialsStore().setMaterial(material);
			});
			return request;
		},

		find({id, remote_path, is_public, content_hash, missing_materials, ignore_ids, order_by, order_dir, page}) {
			const params = {};
			if (id) params.id = id;
			if (remote_path) params.remote_path = remote_path;
			if (is_public === true || is_public === false) params.is_public = is_public;
			if (content_hash) params.content_hash = content_hash;
			if (missing_materials === true) params.missing_materials = true;
			if (ignore_ids) params.ignore_ids = ignore_ids;
			if (order_by) params.order_by = order_by;
			if (order_dir) params.order_dir = order_dir;
			if (page) params.page = page;

			return axios.get(api_v1_resources_find, {params})
				.then(({data}) => {
					this.setMultipleResources(data.data);
					return data;
				})
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},

		lonely({page}) {
			return this.find({missing_materials: true, page});
		},

		replaceResource({oldResourceId, newResourceId}) {
			const request = axios.post(api_v1_resources_replace_with(oldResourceId, newResourceId))
				.then(({data}) => data)
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
			request.then(resource => {
				this.clearResource(oldResourceId);
				this.clearResource(newResourceId);
				this.resources[resource.id] = resource;
				for (const material of resource.materials || []) {
					useMaterialsStore().clearMaterial(material.id ?? material);
				}
			});
			return request;
		},
	},
});
