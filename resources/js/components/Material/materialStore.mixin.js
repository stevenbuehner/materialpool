export default {

	data() {
		return {
			material: null
		};
	},

	methods: {

		goToMaterial(id) {
			this.$router.push(
				{
					name: 'material-detail',
					params: {id: id}
				}
			);
		}
	}

}