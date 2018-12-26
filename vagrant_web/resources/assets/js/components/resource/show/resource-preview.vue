<template>
    <div class="resourcePreview card mb-4" @mouseover="hovered = true" @mouseleave="hovered = false">
        <component :is="previewComponent" :resource="resource"></component>

        <transition name="fade">

            <div class="card-body resPrevMenu pt-0" v-if="hovered">
                <div class="meta">
                    <div v-if="resource.creator">
                        {{$t('pool.Creator')}}:
                        <user-name :user="resource.creator"></user-name>
                    </div>
                </div>

                <slot name="buttons">
                    <slot name="default-buttons">
                        <a v-if="showDownload"
                           class="btn btn-sm btn-outline-primary mb-1"
                           :href="downloadResourceLink(resource)">{{$t('pool.download')}}</a>
                        <router-link v-if="showOpen" :to="{name:'resource-detail', params: {id: resource.id}}"
                                     class="btn btn-sm btn-outline-primary mb-1">{{$t('pool.open')}}
                        </router-link>
                        <router-link v-if="resource.type==='pdf'" :to="routerEditLimitationObject(resource)"
                                     class="btn btn-sm btn-outline-primary mb-1">{{$t('pool.resource-assignments')}}
                        </router-link>
                    </slot>
                    <slot name="additional-buttons"></slot>
                </slot>
            </div>

        </transition>

    </div>
</template>

<script>
    import imagePreview from './image-preview.vue'
    import textPreview from './text-preview.vue'
    import pdfPreview from './pdf-preview.vue'
    import audioPreview from './audio-preview.vue'
    import videoPreview from './video-preview.vue'
    import docPreview from './doc-preview.vue'
    import filePreview from './file-preview.vue'
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

        data() {
            return {
                hovered: false
            }
        },

        computed: {
            previewComponent() {
                return this.resource.type + '-preview';
            }
        },

        methods: {},


        components: {
            UserName,
            imagePreview,
            textPreview,
            pdfPreview,
            audioPreview,
            videoPreview,
            docPreview,
            resPreview,
            filePreview,
        }
    }
</script>

<style type="scss">

    .resourcePreview {
        .fade-enter-active, .fade-leave-active {
            transition: opacity .5s;
        }

        .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */
        {
            opacity: 0;
        }
    }


</style>