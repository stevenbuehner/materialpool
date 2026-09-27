import {useGeneralStore} from '../../apps/main/stores/general';

export default {
	asyncComputed: {
		username: {
			get() {
				return useGeneralStore().currentUser()
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
