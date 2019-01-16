import {api_v1_materials_index} from '../../../../components/serverRoutes'
import axios from '../../axiosInstance';

const state = {
    pageMaterial: {},
};

const getters   = {};
const mutations = {
    setMaterialPage(state, {page, data}) {
        state.pageMaterial[page] = data;
    },
};

const actions = {

    getMaterialPage: async ({commit, dispatch, state}, pageNo) => {


        if (state.pageMaterial.hasOwnProperty(pageNo)) {
            return state.pageMaterial[pageNo];
        } else {
            const response = await axios.get(api_v1_materials_index, {
                params: {page: pageNo}
            });

            const data = response.data;

            commit('setMaterialPage', {page: pageNo, data: data});

            const materials = data.data;
            for (let i in materials) {
                commit('materials/setMaterial', materials[i], {root: true});
            }

            return data;
        }
    }

};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};