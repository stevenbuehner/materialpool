import {resourceDownloadLink, resourceLimitedpdfDownload} from './../serverRoutes'

export default {
    methods: {

        downloadResourceLink(resource) {


            if (!this.hasLimitation(resource)) {
                return resourceDownloadLink(resource);
            }

            switch (resource.type) {
                case 'pdf':
                    return resourceLimitedpdfDownload(resource.pivot.resource_id, resource.pivot.material_id)
                default:
                    return resourceDownloadLink(resource);
            }

        },

        hasLimitation(resource) {
            return resource.pivot && resource.pivot.limitation;
        }

    },

    computed: {}
}