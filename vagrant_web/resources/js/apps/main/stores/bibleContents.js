import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {queue} from '../store/networkQueue';
import {getRangeId} from '../store/helper/bibleverseHelper';
import {useBiblesStore} from './bibles';
import {api_v1_biblecontents_get, api_v1_biblecontents_search_and_get} from '../../../components/serverRoutes';

export const useBibleContentsStore = defineStore('biblecontents', {
	state: () => ({
		cache: {},
	}),

	getters: {
		getBibleverse: state => verseKey => state.cache[verseKey] || false,
	},

	actions: {
		setBibleverse({verseKey, verses}) {
			this.cache[verseKey] = verses;
		},

		clearBibleverse(verseKey) {
			delete this.cache[verseKey];
		},

		get({from, to, bibleUuid}) {
			const normalizedBibleUuid = bibleUuid || null;
			const cacheKey = getRangeId(from, to, normalizedBibleUuid);
			const cachedVerses = this.getBibleverse(cacheKey);

			if (cachedVerses !== false) {
				return Promise.resolve(cachedVerses);
			}

			const route = api_v1_biblecontents_get(from, to, normalizedBibleUuid);
			const loadingPromise = queue.add(() => axios.get(route))
				.then(({data}) => {
					const {bible, verses} = data;
					useBiblesStore().addBible(bible);

					if (normalizedBibleUuid === null) {
						this.setBibleverse({verseKey: cacheKey, verses});
					}

					this.setBibleverse({verseKey: getRangeId(from, to, bible.uuid), verses});
					return verses;
				})
				.catch(error => {
					this.clearBibleverse(cacheKey);
					throw error;
				});

			this.setBibleverse({verseKey: cacheKey, verses: loadingPromise});
			return loadingPromise;
		},

		getMultiple(multipleQueries) {
			return Promise.all(multipleQueries.map(query => this.get(query)));
		},

		searchAndGet({search, bibleUuid}) {
			const normalizedBibleUuid = bibleUuid || null;
			const route = api_v1_biblecontents_search_and_get(search, normalizedBibleUuid);

			return axios.get(route, {params: {search}})
				.then(({data}) => {
					if (data.error) {
						console.error(data.error);
						throw data.error;
					}

					useBiblesStore().addBible(data.bible);

					if (data.bibleverses.length === 1) {
						const {from, to} = data.bibleverses[0];
						this.setBibleverse({
							verseKey: getRangeId(from, to, normalizedBibleUuid),
							verses: data.verses,
						});

						if (normalizedBibleUuid === null) {
							this.setBibleverse({
								verseKey: getRangeId(from, to, data.bible.uuid),
								verses: data.verses,
							});
						}
					}

					return data;
				});
		},
	},
});
