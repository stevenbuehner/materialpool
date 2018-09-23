import {resourceDownloadLink} from './../serverRoutes'

export default {
    methods: {

        downloadResource() {
            window.location = this.resourceDownloadUrl;
        },

    },

    computed: {

        resourceDownloadUrl() {
            return resourceDownloadLink(this.resource);
        },

    }
}