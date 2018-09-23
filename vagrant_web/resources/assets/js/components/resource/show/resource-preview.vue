<template>
    <div class="resource card">
        <component :is="previewComponent" :resource="resource"></component>

        <div class="card-body">
            <div class="meta">
                <span class="author"></span>
            </div>
            <slot name="buttons">

            </slot>
            <slot name="buttons">
                <slot name="default-buttons">
                    <button class="btn btn-outline-primary" @click.prevent="downloadResource">download</button>
                    <router-link :to="{name:'resource-detail', params: {id: resource.id}}"
                                 class="btn btn-outline-primary">{{$t('pool.open')}}
                    </router-link>
                </slot>
                <slot name="additional-buttons"></slot>
            </slot>
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
    import {resourceDownloadLink} from './../../serverRoutes';
    import resourceLinks from './../resource-links.mixin';


    export default {

        mixins: [resourceLinks],

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