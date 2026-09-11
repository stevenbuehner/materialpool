import {defineStore} from 'pinia';
import _clone from 'lodash/_baseClone';
import axios from '../axiosInstance';
import {queue} from '../store/networkQueue';
import {getAllPages} from '../store/helper/paginationHelperQueued';
import {convertErrorResponseToMessage} from '../store/modules/handleErrorsHelper';
import {
	api_v1_keywords_create,
	api_v1_keywords_delete,
	api_v1_keywords_deleteassignment,
	api_v1_keywords_index,
	api_v1_keywords_relations_count,
	api_v1_keywords_show,
	api_v1_keywords_update,
	api_v1_keywords_updateassignment,
	searchGuessKeywords,
} from '../../../components/serverRoutes';

export const useKeywordsStore = defineStore('keywords', {
	state: () => ({
		keywords: {},
		allKeywordsLoaded: false,
	}),

	getters: {
		getKeyword: state => keywordId => state.keywords[keywordId] || false,
		areAllKeywordsLoaded: state => () => state.allKeywordsLoaded,
		getAllKeywords: state => () => Object.values(state.keywords),
	},

	actions: {
		setKeyword(keyword) {
			this.keywords[keyword.id] = _clone(keyword);

			if (this.keywords[keyword.id].pivot) {
				delete this.keywords[keyword.id].pivot;
			}
		},

		removeKeyword(id) {
			delete this.keywords[id];
		},

		setAllKeywordsLoaded(loaded) {
			this.allKeywordsLoaded = !!loaded;
		},

		get(keywordId) {
			const keyword = this.getKeyword(keywordId);

			if (keyword) {
				return Promise.resolve(keyword);
			}

			return axios.get(api_v1_keywords_show(keywordId))
				.then(({data}) => {
					this.setKeyword(data);
					return data;
				});
		},

		async getMultiple(keywordIds) {
			const response = Promise.all(keywordIds.map(
				id => queue.add(() => this.get(id))
			));

			response.then(keywords => this.setMultipleKeywords(keywords));
			return response;
		},

		getAll(forceReload) {
			if (forceReload === true) {
				this.setAllKeywordsLoaded(false);
			} else if (this.areAllKeywordsLoaded()) {
				return Promise.resolve(this.getAllKeywords());
			}

			const resultPromise = getAllPages(api_v1_keywords_index);
			resultPromise.then(allKeywords => {
				this.setMultipleKeywords(allKeywords);
				this.setAllKeywordsLoaded(true);
			});

			return resultPromise;
		},

		relationsCount(keywordsId) {
			return axios.get(api_v1_keywords_relations_count(keywordsId))
				.then(({data}) => data)
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},

		setMultipleKeywords(allKeywords) {
			for (const keyword of allKeywords) {
				this.setKeyword(keyword);
			}
		},

		create({title, type}) {
			const params = {title, type: type || 'key'};

			return axios.post(api_v1_keywords_create, params)
				.then(({data}) => {
					console.info('created Keyword', data);
					this.setKeyword(data);
					return data;
				})
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},

		async createAndAssign({title, type, materialId, relevance}) {
			const keyword = await this.create({title, type});
			return this.updateRelevance({materialId, keywordId: keyword.id, relevance});
		},

		update({id, data}) {
			data._method = 'PUT';
			const result = axios.post(api_v1_keywords_update(id), data)
				.then(response => response.data);

			result
				.then(keyword => {
					this.setKeyword(keyword);

					if (keyword.id !== id) {
						this.removeKeyword(id);
						this.setAllKeywordsLoaded(false);
					}
				})
				.catch(response => console.error(response));

			return result;
		},

		updateRelevance({materialId, keywordId, relevance}) {
			const data = {_method: 'PUT', relevance};

			if (!relevance) {
				delete data.relevance;
			}

			return axios.post(api_v1_keywords_updateassignment(materialId, keywordId), data)
				.then(response => response.data)
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},

		deleteAssignment({materialId, keywordId}) {
			return axios.post(
				api_v1_keywords_deleteassignment(materialId, keywordId),
				{_method: 'DELETE'}
			)
				.then(response => response.data)
				.catch(response => {
					console.error('Failed to remove keyword', this.myKeyword);
					throw response;
				});
		},

		deleteMultipleAssignemts({keywordIds, materialId}) {
			return Promise.all(keywordIds.map(
				id => queue.add(() => this.deleteAssignment({materialId, keywordId: id}))
			));
		},

		delete(id) {
			return axios.delete(api_v1_keywords_delete(id))
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				})
				.then(({data}) => {
					if (data.success !== true) {
						throw 'Unknown error while deleting keyword with id ' + id;
					}

					this.removeKeyword(id);
					return data.success;
				});
		},

		search({searchText, type, limit, page}) {
			type = type || false;
			page = page || 1;
			limit = limit || 20;

			const params = {q: searchText, limit, page};
			if (type) {
				params.t = type;
			}

			return axios.get(searchGuessKeywords, {params})
				.then(({data}) => {
					const keywords = data.data;
					const pagination = {
						from: data.from,
						to: data.to,
						limit: data.per_page,
						total: data.total,
						hasMore: data.current_page < data.last_page,
						current_page: data.current_page,
					};

					if (Array.isArray(keywords) && keywords.length > 0) {
						this.setMultipleKeywords(keywords);
					}

					return {keywords, pagination};
				});
		},

		searchMultiple(searchArray) {
			if (!Array.isArray(searchArray)) {
				console.error('Parameter searchArray is not of type Array');
			}

			return Promise.all(searchArray.map(({searchText, type, limit}) =>
				queue.add(() => this.search({searchText, type, limit}))
			))
				.then(results => {
					const keywords = results.map(result => result.keywords).flat(1);
					return keywords.filter((element, index, collection) =>
						index === collection.findIndex(item => item.id === element.id)
					);
				});
		},
	},
});
