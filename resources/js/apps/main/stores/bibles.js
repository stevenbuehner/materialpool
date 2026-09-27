import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {getAllPages} from '../store/helper/paginationHelperQueued';
import {api_v1_bibles_index, api_v1_bibles_show} from '../../../components/serverRoutes';

export const useBiblesStore = defineStore('bibles', {
	state: () => ({
		allBibles: {},
		allBiblesLoaded: false,
	}),

	getters: {
		getBible: state => uuid => state.allBibles[uuid],
	},

	actions: {
		addBible(bible) {
			this.allBibles[bible.uuid] = bible;
		},

		async get(uuid) {
			const cachedBible = this.getBible(uuid);
			if (cachedBible !== undefined) {
				return cachedBible;
			}

			const {data} = await axios.get(api_v1_bibles_show(uuid));
			this.addBible(data);
			return data;
		},

		getAll() {
			if (this.allBiblesLoaded === true) {
				return Promise.resolve(Object.values(this.allBibles));
			}

			if (this.allBiblesLoaded !== false) {
				return this.allBiblesLoaded;
			}

			const loadingPromise = getAllPages(api_v1_bibles_index)
				.then(bibles => {
					for (const bible of bibles) {
						this.addBible(bible);
					}

					this.allBiblesLoaded = true;
					return Object.values(this.allBibles);
				});

			this.allBiblesLoaded = loadingPromise;
			return loadingPromise;
		},
	},
});
