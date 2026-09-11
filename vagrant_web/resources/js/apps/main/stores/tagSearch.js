import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {searchGuessRoute} from '../../../components/serverRoutes';

export const useTagSearchStore = defineStore('tagsearch', {
	actions: {
		searchTags(searchText) {
			return axios.get(searchGuessRoute, {params: {q: searchText}})
				.then(({data}) => data)
				.catch(response => {
					console.error(response);
					return response;
				});
		},
	},
});
