<template>
    <div class="resource card">
        <component :is="previewComponent" :resource="resource"></component>

        <div class="card-body">
            <div class="meta">
                <div v-if="resource.creator">
                    Creator:
                    <user-name :user="resource.creator"></user-name>
                </div>
            </div>

            <slot name="buttons">
                <slot name="default-buttons">
                    <a v-if="showDownload"
                       class="btn btn-outline-primary btn-sm mb-1"
                       :href="downloadResourceLink(resource)">{{$t('pool.download')}}</a>
                    <router-link v-if="showOpen" :to="{name:'resource-detail', params: {id: resource.id}}"
                                 class="btn btn-outline-primary btn-sm mb-1">{{$t('pool.open')}}
                    </router-link>
                    <router-link v-if="resource.type=='pdf'" :to="{name:'resource-assign', params: {id: resource.id}}"
                                 class="btn btn-outline-primary btn-sm mb-1">{{$t('pool.resource-assignments')}}
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
    import resourceLinks from './../resource-links.mixin';
    import UserName from "../../user/user-name";


    export default {

        mixins: [resourceLinks],

        props: {
            resource: {
                required: true,
                type: Object
            },

            showDownload: {
                type: Boolean,
                required: false,
                default: true
            },

            showOpen: {
                type: Boolean,
                required: false,
                default: true
            },
        },

        computed: {
            previewComponent() {
                return this.resource.type + '-preview';
            }
        },

        methods: {
        },


        components: {
            UserName,
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