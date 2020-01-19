export default {
	props: {
		resource: {
			required: true,
			type: Object
		},

		hovered: {
			type: Boolean,
			required: false,
			default: false
		}
	}
}