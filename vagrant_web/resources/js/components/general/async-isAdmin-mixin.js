export default {
	asyncComputed: {
		isAdmin: {
			get() {
				return this.$store.dispatch('general/isAdmin')
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