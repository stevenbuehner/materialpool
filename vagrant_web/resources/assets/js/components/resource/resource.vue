<template>
    <div class="resource card">
        <component :is="previewComponent" :resource="resource"></component>

        <div class="card-body">
            <div class="meta">
                <span class="author"></span>
            </div>
            <button class="btn btn-outline-primary" @click.prevent="downloadResource">download</button>
            <button class="btn btn-outline-primary" @click.prevent="goToResource">open</button>
        </div>

    </div>
</template>

<script>
    import imagePreview from './image-preview.vue'
    import textPreview from './text-preview.vue'
    import pdfPreview from './pdf-preview.vue'
    import audioPreview from './audio-preview.vue'
    import videoPreview from './video-preview.vue'
    import docPreview from './doc-preview.vue'
    import resPreview from './res-preview.vue'
    import {resourceDownloadLink, resourceEditLink} from './../serverRoutes';


    export default {
        props: {
            resource: {
                required: true,
                type: Object
            }
        },

        computed: {
            previewComponent() {
                return this.resource.type + '-preview';
            }
        },

        methods: {

            downloadResource() {
                window.location = resourceDownloadLink(this.resource);
            },

            goToResource() {
                window.location = resourceEditLink(this.resource);
            }
        },


        components: {
            imagePreview,
            textPreview,
            pdfPreview,
            audioPreview,
            videoPreview,
            docPreview,
            resPreview
        }
    }
</script>

<style scoped>

    .resource {
    }

</style>