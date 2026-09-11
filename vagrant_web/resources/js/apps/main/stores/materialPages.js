import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {api_v1_materials_index} from '../../../components/serverRoutes';
import {useMaterialsStore} from './materials';

export const useMaterialPagesStore = defineStore('materialapp', {
	state: () => ({pageMaterial: {}}),

	actions: {
		setMaterialPage({page, data}) {
			this.pageMaterial[page] = data;
		},

		async getMaterialPage(pageNo) {
			if (Object.hasOwn(this.pageMaterial, pageNo)) {
				return this.pageMaterial[pageNo];
			}

			const {data} = await axios.get(api_v1_materials_index, {params: {page: pageNo}});
			this.setMaterialPage({page: pageNo, data});
			for (const material of data.data) useMaterialsStore().setMaterial(material);
			return data;
		},
	},
});
