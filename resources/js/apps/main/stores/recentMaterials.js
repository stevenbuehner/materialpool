import {defineStore} from 'pinia';
import {uniqueArray} from '../../../helper/ArrayHelper';

export const useRecentMaterialsStore = defineStore('recentmaterials', {
	state: () => ({
		maxCount: 5,
		recentMaterialIds: [],
	}),

	getters: {
		getRecentMaterialIds: state => state.recentMaterialIds,
	},

	actions: {
		addRecentMaterialId(materialId) {
			this.recentMaterialIds.push(materialId);
			this.recentMaterialIds = uniqueArray(this.recentMaterialIds);

			if (this.recentMaterialIds.length > this.maxCount) {
				this.recentMaterialIds.shift();
			}
		},
	},
});
