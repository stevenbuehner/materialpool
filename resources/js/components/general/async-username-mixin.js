import {useGeneralStore} from '../../apps/main/stores/general';

export default {
	asyncComputed: {
		username: {
			get() {
				const store = useGeneralStore();
				const currentName = store.generalOptions?.user?.name;
				if (currentName) return Promise.resolve(currentName);
				return store.currentUser()
				           .then((user) => {
					           return user.name;
				           });
			},
			default: 'User',
			/* watch() {
						this.forceReload
					}*/
		},
	}
}
