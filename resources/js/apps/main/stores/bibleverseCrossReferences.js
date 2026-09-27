import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {queue} from '../store/networkQueue';
import {getRangeId} from '../store/helper/bibleverseHelper';
import {convertErrorResponseToMessage} from '../store/modules/handleErrorsHelper';
import {api_v2_bibleverse_cross_references} from '../../../components/serverRoutes';

export const useBibleverseCrossReferencesStore = defineStore('bibleverseCrossReferences', {
	state: () => ({
		crossRefs: {},
	}),

	getters: {
		getCrossRefs: state => rangeId => state.crossRefs[rangeId]?.items ?? false,
		getCrossRefsCount: state => rangeId => state.crossRefs[rangeId]?.count ?? false,
	},

	actions: {
		clearCrossRefs(rangeId) {
			delete this.crossRefs[rangeId];
		},

		ensureRange(rangeId) {
			if (!this.crossRefs[rangeId]) {
				this.crossRefs[rangeId] = {items: [], count: false};
			}
		},

		setCrossRefsCount({rangeId, count}) {
			this.ensureRange(rangeId);
			this.crossRefs[rangeId].count = count;
		},

		setCrossRefs({rangeId, refs}) {
			this.ensureRange(rangeId);
			this.crossRefs[rangeId].items = refs;
		},

		async get({from, to = from, maximum = 50}) {
			const rangeId = getRangeId(from, to);
			const cachedCrossRefs = this.getCrossRefs(rangeId);
			let countTotal = this.getCrossRefsCount(rangeId);

			if (cachedCrossRefs !== false
				&& countTotal !== false
				&& (cachedCrossRefs.length >= maximum
					|| (countTotal <= maximum && countTotal === cachedCrossRefs.length))) {
				return cachedCrossRefs.slice(0, maximum);
			}

			const collection = [];
			let url = api_v2_bibleverse_cross_references(from, to);

			do {
				try {
					const {data} = await axios.get(url);
					collection.push(...data.data);

					if (countTotal !== data.total) {
						this.setCrossRefsCount({rangeId, count: data.total});
						countTotal = data.total;
					}

					url = data.next_page_url;
				} catch (exception) {
					return convertErrorResponseToMessage(exception);
				}
			} while (url !== null && collection.length < maximum);

			this.setCrossRefs({rangeId, refs: collection});
			return collection.slice(0, maximum);
		},

		async getCount({from, to = from}) {
			const rangeId = getRangeId(from, to);
			const countTotal = this.getCrossRefsCount(rangeId);

			if (countTotal !== false) {
				return countTotal;
			}

			await this.get({from, to});
			return this.getCrossRefsCount(rangeId);
		},

		getMultiple(ranges) {
			return Promise.all(ranges.map(({from, to, maximum}) => queue.add(
				() => this.get({from, to, maximum})
			)));
		},
	},
});
