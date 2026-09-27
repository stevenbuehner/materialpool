import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {dayjs} from '../../../helper/datetime.mixin';
import {
	api_v2_materialusage_delete,
	api_v2_materialusage_index,
	api_v2_materialusage_store,
	api_v2_materialusage_update,
} from '../../../components/serverRoutes';
import {convertErrorResponseToMessage} from '../store/modules/handleErrorsHelper';

export const useMaterialUsagesStore = defineStore('materialusages', {
	state: () => ({
		usagesByMaterialId: {},
	}),

	getters: {
		hasMaterialUsages: state => materialId => Object.hasOwn(state.usagesByMaterialId, materialId),
		getCachedMaterialUsages: state => materialId => state.usagesByMaterialId[materialId] ?? null,
	},

	actions: {
		setMaterialUsages(usages) {
			if (Array.isArray(usages) && usages.length > 0) {
				this.usagesByMaterialId[usages[0].material_id] = usages;
			} else {
				console.error('setMaterialUsages with empty array');
			}
		},

		setMaterialUsage(usage) {
			if (!Object.hasOwn(usage, 'material_id')) {
				return;
			}

			const materialId = usage.material_id;
			if (!Array.isArray(this.usagesByMaterialId[materialId])) {
				this.usagesByMaterialId[materialId] = [];
			}

			const index = this.usagesByMaterialId[materialId].findIndex(item => item.id === usage.id);
			if (index === -1) {
				this.usagesByMaterialId[materialId].push(usage);
			} else {
				this.usagesByMaterialId[materialId][index] = usage;
			}
		},

		clearMaterialUsages(materialId) {
			delete this.usagesByMaterialId[materialId];
		},

		clearMaterialUsage({material_id: materialId, id}) {
			if (Array.isArray(this.usagesByMaterialId[materialId])) {
				this.usagesByMaterialId[materialId] = this.usagesByMaterialId[materialId]
					.filter(usage => usage.id !== id);
			}
		},

		hasMaterialUsagesCached(materialId) {
			return this.hasMaterialUsages(materialId);
		},

		getMaterialUsages(materialId) {
			if (this.hasMaterialUsages(materialId)) {
				return Promise.resolve(this.getCachedMaterialUsages(materialId));
			}

			return axios.get(api_v2_materialusage_index(materialId), {})
				.then(({data}) => {
					if (data.length > 0) {
						this.setMaterialUsages(data);
						return this.getCachedMaterialUsages(materialId);
					}

					this.clearMaterialUsages(materialId);
					return [];
				})
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},

		addMaterialUsage({material_id, used_by_id}) {
			return this.updateMaterialUsage({material_id, used_by_id});
		},

		updateMaterialUsage({material_id, id, datetime, place, reason, used_by_id}) {
			const url = id === undefined
				? api_v2_materialusage_store(material_id)
				: api_v2_materialusage_update(material_id, id);

			return axios.post(url, {
				datetime: dayjs(datetime === undefined ? new Date() : datetime).format(),
				place: place === undefined ? '' : place,
				reason: reason === undefined ? '' : reason,
				used_by_id: used_by_id === undefined ? null : used_by_id,
			})
				.then(({data}) => {
					this.setMaterialUsage(data);
					return data;
				})
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},

		deleteMaterialUsage({material_id, id}) {
			return axios.post(api_v2_materialusage_delete(material_id, id), {_method: 'DELETE'})
				.then(response => {
					if (response?.data?.success !== true) {
						const message = response?.data?.message ?? response?.data?.error ?? 'undefined error';
						throw new Error(message);
					}

					this.clearMaterialUsage({material_id, id});
					return true;
				})
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},
	},
});
