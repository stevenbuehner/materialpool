export default {
	props: {
		text: {
			type: String,
			required: true
		},

		item: {
			type: Object,
			required: true
		},

		icon: {
			type: String,
			required: false,
			default: ''
		},

		disabled: {
			type: Boolean,
			default: false
		},

		descendants: {
			type: Array,
			required: false,
			default() {
				return [];
			}
		},
	}
}