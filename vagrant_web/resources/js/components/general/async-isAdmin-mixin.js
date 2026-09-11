import {useGeneralStore} from '../../apps/main/stores/general';

export default {
	asyncComputed: {
		isAdmin: {
			get() {
				return useGeneralStore().isAdmin()
				           .then((isAdmin) => {
					           return isAdmin;
				           });
			},
			default: false,
			/* watch() {
						this.forceReload
					}*/
		}
	}
}
