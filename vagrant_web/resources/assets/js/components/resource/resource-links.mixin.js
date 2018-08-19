import {resourceDownloadLink, resourceEditLink} from './../serverRoutes'

export default {
    methods: {

        downloadResource() {
            window.location = this.resourceDownloadUrl;
        },

        goToResource() {
            window.location = this.resourceUrl;
        }
    },

    computed: {
        resourceUrl() {
            return resourceEditLink(this.resource);
        },

        resourceDownloadUrl() {
            return resourceDownloadLink(this.resource);
        },
    }
}