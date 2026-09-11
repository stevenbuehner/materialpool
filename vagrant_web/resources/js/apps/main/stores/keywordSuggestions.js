import {defineStore} from 'pinia';
import {cloneDeep} from 'lodash';
import axios from '../axiosInstance';
import {queue} from '../store/networkQueue';
import {convertErrorResponseToMessage} from '../store/modules/handleErrorsHelper';
import {api_v2_keywords_suggestions} from '../../../components/serverRoutes';
import {useKeywordsStore} from './keywords';

export const useKeywordSuggestionsStore = defineStore('keywordsSuggestions', {
	state: () => ({
		keywSug: {},
		pendingGets: {},
	}),

	getters: {
		getKeywordSuggestions: state => id => state.keywSug[id]?.items ?? false,
		getKeywordSuggestionsCount: state => id => state.keywSug[id]?.count ?? false,
	},

	actions: {
		clearKeywordSuggestions(id) {
			delete this.keywSug[id];
		},

		ensureKeywordSuggestions(id) {
			if (!this.keywSug[id]) {
				this.keywSug[id] = {items: [], count: false};
			}
		},

		setKeywordSuggestionsCount({id, count}) {
			this.ensureKeywordSuggestions(id);
			this.keywSug[id].count = count;
		},

		setKeywordSuggestions({id, items}) {
			this.ensureKeywordSuggestions(id);
			this.keywSug[id].items = items;
		},

		async get({id, maximum = 50}) {
			const cachedSuggestions = this.getKeywordSuggestions(id);
			const countTotal = this.getKeywordSuggestionsCount(id);

			if (cachedSuggestions !== false
				&& countTotal !== false
				&& (cachedSuggestions.length >= maximum
					|| (countTotal <= maximum && countTotal === cachedSuggestions.length))) {
				return cachedSuggestions.slice(0, maximum);
			}

			if (this.pendingGets[id]) {
				const pendingResult = await this.pendingGets[id];
				return Array.isArray(pendingResult) ? this.get({id, maximum}) : pendingResult;
			}

			const request = this.load({id, maximum, countTotal});
			this.pendingGets[id] = request;

			try {
				return await request;
			} finally {
				if (this.pendingGets[id] === request) {
					delete this.pendingGets[id];
				}
			}
		},

		async load({id, maximum, countTotal: initialCountTotal}) {
			let countTotal = initialCountTotal;

			const collection = [];
			let url = api_v2_keywords_suggestions(id);

			do {
				try {
					const {data} = await axios.get(url);
					collection.push(...data.data);

					if (countTotal !== data.total) {
						this.setKeywordSuggestionsCount({id, count: data.total});
						countTotal = data.total;
					}

					url = data.next_page_url;
				} catch (exception) {
					return convertErrorResponseToMessage(exception);
				}
			} while (url !== null && collection.length < maximum);

			this.setKeywordSuggestions({id, items: collection});
			const publicKeywords = collection.map(keyword => {
				const publicKeyword = cloneDeep(keyword);
				delete publicKeyword.relevance;
				return publicKeyword;
			});
			useKeywordsStore().setMultipleKeywords(publicKeywords);

			return collection.slice(0, maximum);
		},

		async getCount(id) {
			const countTotal = this.getKeywordSuggestionsCount(id);

			if (countTotal !== false) {
				return countTotal;
			}

			await this.get({id});
			return this.getKeywordSuggestionsCount(id);
		},

		getMultiple(ranges) {
			return Promise.all(ranges.map(({id, maximum}) => queue.add(
				() => this.get({id, maximum})
			)));
		},
	},
});
