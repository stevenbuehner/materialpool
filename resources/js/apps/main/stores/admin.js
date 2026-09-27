import {defineStore} from 'pinia';
import axios from '../axiosInstance';
import {
	api_v2_admin_group,
	api_v2_admin_groups,
	api_v2_admin_permissions,
	api_v2_admin_user,
	api_v2_admin_user_invitation,
	api_v2_admin_users,
} from '../../../components/serverRoutes';
import {convertErrorResponseToMessage} from '../store/modules/handleErrorsHelper';

export const useAdminStore = defineStore('admin', {
	state: () => ({
		users: [],
		groups: [],
		permissions: [],
		pagination: {current_page: 1, last_page: 1, total: 0},
		loading: false,
		error: null,
	}),
	actions: {
		async loadUsers(params = {}) {
			return this.run(async () => {
				const {data} = await axios.get(api_v2_admin_users, {params});
				this.users = data.data;
				this.pagination = {current_page: data.current_page, last_page: data.last_page, total: data.total};
			});
		},
		async loadReferenceData() {
			return this.run(async () => {
				const [groups, permissions] = await Promise.all([
					axios.get(api_v2_admin_groups),
					axios.get(api_v2_admin_permissions),
				]);
				this.groups = groups.data.data;
				this.permissions = permissions.data.data;
			});
		},
		async createUser(payload) {
			return this.run(async () => (await axios.post(api_v2_admin_users, payload)).data);
		},
		async updateUser({id, ...payload}) {
			return this.run(async () => (await axios.patch(api_v2_admin_user(id), payload)).data);
		},
		async resendInvitation(id) {
			return this.run(async () => (await axios.post(api_v2_admin_user_invitation(id))).data);
		},
		async createGroup(payload) {
			return this.run(async () => (await axios.post(api_v2_admin_groups, payload)).data);
		},
		async updateGroup({id, ...payload}) {
			return this.run(async () => (await axios.patch(api_v2_admin_group(id), payload)).data);
		},
		async deleteGroup(id) {
			return this.run(async () => axios.delete(api_v2_admin_group(id)));
		},
		async run(operation) {
			this.loading = true;
			this.error = null;
			try {
				return await operation();
			} catch (error) {
				this.error = convertErrorResponseToMessage(error);
				throw error;
			} finally {
				this.loading = false;
			}
		},
	},
});
