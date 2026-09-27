import {defineStore} from 'pinia';
import {
	api_v1_general_options,
	api_v1_users_store_settings,
	api_v1_users_view,
} from '../../../components/serverRoutes';
import axios from '../axiosInstance';
import {convertErrorResponseToMessage} from '../store/modules/handleErrorsHelper';
import {extractValueById, overrideValueById, removeValueById, verifyStructure} from '../store/helper/idExplode';
import {userHasPermission} from '../authorization';

const USER_SETTINGS_ID = 'frontend_user_settings';

export const useGeneralStore = defineStore('general', {
	state: () => ({generalOptions: null, users: {}}),

	getters: {
		getOptions: state => () => state.generalOptions,
		getUserFromCache: state => id => state.users[id] || null,
	},

	actions: {
		setOptions(options) {
			this.generalOptions = options;
		},
		clearOptions() {
			this.generalOptions = null;
		},
		setUser(user) {
			this.users[user.id] = user;
			if (this.generalOptions?.user?.id === user.id) this.generalOptions.user = user;
		},
		setUserPromise({promise, id}) {
			this.users[id] = promise;
		},
		clearUser(id) {
			delete this.users[id];
		},

		options() {
			const options = this.getOptions();
			if (options === null) {
				const request = axios.get(api_v1_general_options)
					.then(({data}) => data)
					.catch(result => {
						this.clearOptions();
						return convertErrorResponseToMessage(result);
					});
				this.setOptions(request);
				request.then(data => {
					if (data?.user && typeof data.user === 'object') {
						this.setOptions(data);
						this.setUser(data.user);
					}
				});
				return request;
			}
			if (typeof options.then === 'function') return options;
			return Promise.resolve(options);
		},

		maxUploadSize() {
			return this.options().then(allOptions => allOptions?.server?.max_upload);
		},
		currentUser() {
			return this.options().then(allOptions => allOptions?.user);
		},
		async currentUserId() {
			const currentUser = await this.currentUser();
			return currentUser?.id;
		},

		getUser(id) {
			const user = this.getUserFromCache(id);
			if (user === null) {
				const request = axios.get(api_v1_users_view(id))
					.then(({data}) => data)
					.catch(result => {
						this.clearUser(id);
						return convertErrorResponseToMessage(result);
					});
				this.setUserPromise({promise: request, id});
				request.then(loadedUser => {
					if (loadedUser?.id !== undefined) this.setUser(loadedUser);
				});
				return request;
			}
			if (typeof user.then === 'function') return user;
			return Promise.resolve(user);
		},

		isAdmin() {
			return this.currentUser().then(({is_admin}) => is_admin || false);
		},
		hasPermission(permission) {
			return this.currentUser().then(user => userHasPermission(user, permission));
		},
		systemName() {
			return this.options().then(allOptions => allOptions.systemname || 'MaterialPool Default');
		},
		currentUserSetting({settingId, defaultValue}) {
			return this.currentUser()
				.then(currentUser => verifyStructure(currentUser[USER_SETTINGS_ID]))
				.then(allSettings => extractValueById(settingId, allSettings, defaultValue));
		},
		getUserSetting({userId, settingId, defaultValue}) {
			return this.getUser(userId)
				.then(user => verifyStructure(user[USER_SETTINGS_ID]))
				.then(allSettings => extractValueById(settingId, allSettings, defaultValue));
		},

		storeCompleteUserSettings({userId, allSettings}) {
			return axios.post(api_v1_users_store_settings(userId), {data: allSettings})
				.then(({data}) => data)
				.then(data => {
					this.setUser(data);
					return data;
				})
				.catch(result => convertErrorResponseToMessage(result));
		},
		async storeUserSetting({userId, settingId, data}) {
			const allUserSettings = await this.getUserSetting({userId});
			const newUserSettings = overrideValueById(settingId, allUserSettings, data);
			return this.storeCompleteUserSettings({userId, allSettings: newUserSettings});
		},
		async storeCurrentUserSetting({settingId, data}) {
			const user = await this.currentUser();
			return this.storeUserSetting({userId: user.id, settingId, data});
		},
		async removeUserSetting({userId, settingId}) {
			const allUserSettings = await this.getUserSetting({userId});
			const newUserSettings = removeValueById(settingId, allUserSettings);
			return this.storeCompleteUserSettings({userId, allSettings: newUserSettings});
		},
		async removeCurrentUserSetting(settingId) {
			const user = await this.currentUser();
			return this.removeUserSetting({userId: user.id, settingId});
		},
	},
});
