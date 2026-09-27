import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {api_v2_users_find} from '../../../components/serverRoutes';
import {convertErrorResponseToMessage} from '../store/modules/handleErrorsHelper';

export const useUsersStore = defineStore('users', {
	state: () => ({
		users: {},
	}),

	getters: {
		hasUser: state => id => state.users[id] !== undefined,
		getUser: state => id => state.users[id],
	},

	actions: {
		setUser(user) {
			this.users[user.id] = user;
		},

		setUsers(users) {
			for (const user of users) {
				this.setUser(user);
			}
		},

		clearUser(id) {
			delete this.users[id];
		},

		search({search, limit}) {
			const params = {s: search};

			if (limit) {
				params.limit = limit;
			}

			return axios.get(api_v2_users_find, {params})
				.then(({data}) => {
					if (data && data.length > 0) {
						this.setUsers(data);
					}

					return data;
				})
				.catch(response => {
					throw convertErrorResponseToMessage(response);
				});
		},
	},
});
