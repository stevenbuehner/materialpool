import {uniqueArray} from "../../../../helper/ArrayHelper";


const state = {
	maxCount: 5,
	recentMaterialIds: []
};

const getters = {
	getRecentMaterialIds: (state) => {
		return state.recentMaterialIds;
	},
};
const mutations = {
	addRecentMaterialId(state, materialId) {

		state.recentMaterialIds.push(materialId)
		state.recentMaterialIds = uniqueArray(state.recentMaterialIds);

		if (state.recentMaterialIds.length > state.maxCount) {
			state.recentMaterialIds.shift();
		}

	},
};

const actions = {};

export default {
	namespaced: true,
	state,
	getters,
	actions,
	mutations
};