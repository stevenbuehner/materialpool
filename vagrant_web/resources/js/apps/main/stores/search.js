import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {searchUrl} from '../../../components/serverRoutes';
import {useMaterialsStore} from './materials';

const MAX_CACHE_HISTORY = 20;

export const useSearchStore = defineStore('search', {
	state: () => ({
		selectedSearchValues: {},
		searchCacheHistory: [],
		searchCache: {},
	}),

	getters: {
		hasCacheEntry: state => query => !!state.searchCache[JSON.stringify(query)],
	},

	actions: {
		getCacheEntry(query) {
			const jsonQuery = JSON.stringify(query);
			const promise = this.searchCache[jsonQuery];
			const index = this.searchCacheHistory.indexOf(jsonQuery);

			if (index !== -1) {
				this.searchCacheHistory.push(this.searchCacheHistory.splice(index, 1)[0]);
			}

			return promise;
		},

		putSearchCache({query, promise}) {
			const jsonQuery = JSON.stringify(query);

			if (this.searchCache[jsonQuery]) {
				this.searchCacheHistory = this.searchCacheHistory.filter(entry => entry !== jsonQuery);
			} else if (this.searchCacheHistory.length >= MAX_CACHE_HISTORY) {
				const oldestQuery = this.searchCacheHistory.shift();
				delete this.searchCache[oldestQuery];
			}

			this.searchCache[jsonQuery] = promise;
			this.searchCacheHistory.push(jsonQuery);
		},

		removeSearchCache(query) {
			const jsonQuery = JSON.stringify(query);
			delete this.searchCache[jsonQuery];
			this.searchCacheHistory = this.searchCacheHistory.filter(entry => entry !== jsonQuery);
		},

		setSelectedSearchValues(value) {
			this.selectedSearchValues = value;
		},

		materials({query, page = 1, per_page = 30, orderBy = null}) {
			const requestData = {
				q: query,
				page: page || 1,
				per_page: per_page || 30,
			};

			if (orderBy) {
				requestData.order_by = orderBy;
			}

			if (this.hasCacheEntry(requestData)) {
				return this.getCacheEntry(requestData);
			}

			const request = axios.post(searchUrl, requestData)
				.then(({data}) => {
					const materials = data.data;
					const paging = {
						current_page: data.current_page,
						from: data.from,
						last_page: data.last_page,
						next_page_url: data.next_page_url,
						per_page: data.per_page,
						prev_page_url: data.prev_page_url,
						to: data.to,
						total: data.total,
					};

					for (const material of materials) {
						useMaterialsStore().setMaterial(material);
					}

					return {materials, paging};
				})
				.catch(response => {
					this.removeSearchCache(requestData);
					throw response.message;
				});

			this.putSearchCache({query: requestData, promise: request});
			return request;
		},

		materialsWithParams({material, page}) {
			const searchQuery = [];

			if (material.title) {
				searchQuery.push({
					type: '*',
					text: material.title,
				});
			}

			return this.materials({query: [searchQuery], page});
		},
	},
});
