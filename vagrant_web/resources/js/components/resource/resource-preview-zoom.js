export const previewRegistrationContainer = [];

export function getOrderedPreviewZoomImages(startComponent) {

	let startIndex = 0;
	let allImages  = [];

	previewRegistrationContainer.forEach((comp, ind) => {
		if (comp === startComponent) {
			startIndex = allImages.length;
		}

		const imgs = comp.getPreviewZoomImages();
		allImages.push(...imgs);
	});

	return {
		data: allImages,
		start: startIndex
	};

}


export default {
	beforeMount() {
		this._registerThisComponentForResourcePreview();
	},

	beforeUnmount() {
		this._unregisterThisComponentForResourcePreview();
	},

	methods: {
		_registerThisComponentForResourcePreview() {
			previewRegistrationContainer.push(this);

			// console.log('registered', previewRegistrationContainer);
		},

		_unregisterThisComponentForResourcePreview() {

			// Remove Component
			const ind = previewRegistrationContainer.findIndex((vueJsInstance) => vueJsInstance === this);
			if (ind >= 0) {
				previewRegistrationContainer.splice(ind, 1);
			}

			// console.log('unregistered', previewRegistrationContainer);

		},

		_emitPreviewZoomRequest() {
			this.$emit('preview-zoom-request', this);
		},

		getPreviewZoomImages() {

			const images = this._getPreviewZoomImagesAndTitles();

			return images.map(({src, title}) => {

				if (!title) {
					title = '';
				}

				return {
					src,
					title,
					resourceId: this.resource.id,
					registeringComponent: this.$options.name
				}

			});
		},

		// OVERRIDE IN EACH Module
		/*
		_getPreviewZoomImagesAndTitles() {
			return [{
				src: '',
				title: 'myTitle'
			}];
		},
		*/

	},
}
