export default {
	asyncComputed: {
		username: {
			get() {
				return this.$store.dispatch('general/currentUser')
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