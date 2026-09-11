import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {queue} from '../store/networkQueue';
import {convertErrorResponseToMessage} from '../store/modules/handleErrorsHelper';
import {
	api_v1_bibleverse_deleteassignment,
	api_v1_bibleverse_updateassignment,
	api_v1_bibleverses_create,
	api_v1_bibleverses_show,
	searchGuessBibleverses,
} from '../../../components/serverRoutes';

export const useBibleversesStore = defineStore('bibleverses', {
	state: () => ({
		bibleverses: {},
	}),

	getters: {
		getBibleverse: state => bibleverseId => state.bibleverses[bibleverseId] || false,
	},

	actions: {
		clearBibleverse(bibleverseId) {
			delete this.bibleverses[bibleverseId];
		},

		setBibleverse(bibleverse) {
			this.bibleverses[bibleverse.id] = bibleverse;
		},

		get(bibleverseId) {
			const cachedBibleverse = this.getBibleverse(bibleverseId);

			if (cachedBibleverse) {
				return Promise.resolve(cachedBibleverse);
			}

			return axios.get(api_v1_bibleverses_show(bibleverseId))
				.then(({data}) => {
					this.setBibleverse(data);
					return data;
				})
				.catch(response => convertErrorResponseToMessage(response));
		},

		getMultiple(bibleverseIds) {
			return Promise.all(bibleverseIds.map(
				id => queue.add(() => this.get(id))
			));
		},

		create({from, to}) {
			return axios.post(api_v1_bibleverses_create, {from, to})
				.then(({data}) => data)
				.catch(response => convertErrorResponseToMessage(response));
		},

		async createAndAssign({from, to, materialId, relevance}) {
			const bibleverse = await this.create({from, to});

			return this.updateRelevance({
				materialId,
				bibleverseId: bibleverse.id,
				relevance,
			});
		},

		updateRelevance({materialId, bibleverseId, relevance}) {
			const data = {_method: 'PUT', relevance};

			if (!relevance) {
				delete data.relevance;
			}

			return axios.post(api_v1_bibleverse_updateassignment(materialId, bibleverseId), data)
				.then(({data: bibleverse}) => bibleverse)
				.catch(response => convertErrorResponseToMessage(response));
		},

		deleteAssignment({materialId, bibleverseId}) {
			return axios.post(
				api_v1_bibleverse_deleteassignment(materialId, bibleverseId),
				{_method: 'DELETE'}
			)
				.then(({data}) => data)
				.catch(response => convertErrorResponseToMessage(response));
		},

		deleteMultipleAssignemts({bibleverseIds, materialId}) {
			return Promise.all(bibleverseIds.map(
				id => queue.add(() => this.deleteAssignment({materialId, bibleverseId: id}))
			));
		},

		search(searchText) {
			return axios.post(searchGuessBibleverses, {q: searchText})
				.then(({data}) => data)
				.catch(response => {
					throw response.data;
				});
		},
	},
});
