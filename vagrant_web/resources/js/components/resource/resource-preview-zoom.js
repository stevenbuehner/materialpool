import {resourceDownloadLink, resourceLimitedPdfDownload} from '../serverRoutes'

export default {
	methods: {

		downloadResourceLink(resource) {


			if (!this.hasLimitation(resource)) {
				return resourceDownloadLink(resource);
			}

			switch (resource.type) {
				case 'pdf':
					return resourceLimitedPdfDownload(resource.pivot.resource_id, resource.pivot.material_id)
				default:
					return resourceDownloadLink(resource);
			}

		},

		routerEditLimitationObject(resource, pivotOverride) {

			switch (resource.type) {
				case 'pdf':
				case 'doc':

					let query = {};
					let pivot = undefined;


					if (pivotOverride !== undefined) {
						pivot = pivotOverride;
					} else if (resource.pivot !== undefined) {
						pivot = resource.pivot;
					}

					if (pivot && pivot.limitation && Array.isArray(pivot.limitation.pages)) {
						query.selection = pivot.limitation.pages.join(',');
					}

					return {
						name: 'resource-page-assign',
						params: {
							id: resource.id,
						},
						query: query
					};
				default:
					return {};
			}

		},

		hasLimitation(resource) {
			return resource.pivot && resource.pivot.limitation;
		}

	},

	computed: {}
}