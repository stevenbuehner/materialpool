<template>
    <div class="resourceDetail jumbotron">
        <component :is="detailComponent" :resource="resource" class="lead"
                   @resource-updated="$emit('resource-updated', $event)"></component>

        <hr>

        <div>
            <div class="meta mb-2">
                <div class="notes" v-if="resource.notes && resource.notes.length > 0">Notiz: {{resource.notes}}</div>
                <div class="originalFilename" v-if="resource.original_filename !== undefined">
                    {{$t('pool.Filename')}}: {{resource.original_filename}}

                    <span class="page_count" v-if="resource.page_count">
                        ({{resource.page_count}} {{$tc('pool.Page', resource.page_count)}})
                    </span>

                </div>
                <div class="limitation" v-if="resource.pivot">
                    {{$t('pool.Limitation')}}: {{resource.pivot.limitation || $t('pool.none')}}
                </div>

                <div class="creator">{{$t('pool.Creator-ID')}}: {{resource.created_by}}</div>
                <div class="resource-id">{{$t('pool.Resource-ID')}}: {{resource.id}}</div>
            </div>
            <slot name="buttons">
                <slot name="default-buttons">
                    <a v-if="showDownload"
                       class="btn btn-outline-primary mb-1"
                       :href="downloadResourceLink(resource)">{{$t('pool.download')}}</a>
                    <router-link v-if="showOpen" :to="{name:'resource-detail', params: {id: resource.id}}"
                                 class="btn btn-outline-primary mb-1">{{$t('pool.open')}}
                    </router-link>
                    <router-link v-if="resource.type==='pdf' || resource.type==='doc'" :to="routerEditLimitationObject(resource, resource.pivot)"
                                 class="btn btn-outline-primary  mb-1">{{$t('pool.page-assignments')}}
                    </router-link>
                    <button v-if="showDelete"
                            class="btn btn-outline-danger mb-1"
                            @click="btnDeleteResource(resource)"
                            :title="$t('pool.Delete-resource')">{{$t('pool.delete')}}
                    </button>
                </slot>
                <slot name="additional-buttons"></slot>
            </slot>
        </div>

    </div>
</template>

<script>
    import imageDetail from './image-detail.vue'
    import textDetail from './text-detail.vue'
    import pdfDetail from './pdf-detail.vue'
    import audioDetail from './audio-detail.vue'
    import videoDetail from './video-preview.vue'
    import docDetail from './doc-detail.vue'
    import resDetail from './res-preview.vue'
    import fileDetail from './file-detail.vue'
    import resourceLinks from '../resource-links.mixin';


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

            showDelete: {
                type: Boolean,
                required: false,
                default: true
            },
        },

        computed: {
            detailComponent() {
                return this.resource.type + '-detail';
            }
        },

        methods: {
            btnDeleteResource(resource) {

                resource = resource || this.resource;

                if (resource.materials === undefined) {
                    this.$store.dispatch('resources/get', this.resource.id)
                        .then((resource) => {
                            this.btnDeleteResource(resource);
                        });
                } else if (resource.materials.length > 0) {
                    alert('Löschen nicht möglich. Materialien sind noch zugewwiesen!')
                } else {
                    this.$store.dispatch('resources/deleteResource', resource.id)
                        .then(() => {
                            this.$router.go(-1);
                        });
                }

            }
        },


        components: {
            imageDetail,
            textDetail,
            pdfDetail,
            audioDetail,
            videoDetail,
            docDetail,
            resDetail,
            fileDetail,
        }
    }
</script>

<style scoped>

    .resourceDetail {
    }

    .meta {
        color: grey;
        font-size: smaller;
    }

</style>