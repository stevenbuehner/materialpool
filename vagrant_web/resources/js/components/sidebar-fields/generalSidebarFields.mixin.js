export default {

	props: {
		name: {
			type: String,
			required: false,
			default: ''
		},

		placeholder: {
			type: String,
			required: false,
			default: null
		},

		value: {
			required: true
		},

		disabled: {
			type: Boolean,
			required: false,
			default: false
		},

	},


	model: {
		prop: 'value',
		event: 'input'
	},


	computed: {
		getPlaceholder() {
			return this.placeholder !== null ? this.placeholder : this.name;
		}
	},

};