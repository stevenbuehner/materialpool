import {resourceDownloadLink, resourceEditLink} from './../serverRoutes'

export default {
    methods: {

        downloadResource() {
            window.location = resourceDownloadLink(this.resource);
        },

        goToResource() {
            window.location = resourceEditLink(this.resource);
        }
    },
}