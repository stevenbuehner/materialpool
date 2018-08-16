<template>
    <div class="resourceDetail jumbotron">
        <component :is="detailComponent" :resource="resource" class="lead"></component>

        <hr>

        <div>
            <div class="meta mb-2">
                <div class="notes" v-if="resource.notes.length > 0">Notiz: {{resource.notes}}</div>
                <div class="originalFilename" v-if="resource.original_filename !== undefined">Dateiname: {{resource.original_filename}}</div>
                <div class="limitation" v-if="resource.pivot && resource.pivot.limitation">Limitation: {{resource.pivot.limitation}}</div>
                <div class="creator">Ersteller-ID: {{resource.created_by}}</div>
            </div>
            <button class="btn btn-outline-primary" @click.prevent="downloadResource">download</button>
            <button class="btn btn-outline-primary" @click.prevent="goToResource">open</button>
        </div>

    </div>
</template>

<script>
    import imageDetail from './image-detail.vue'
    import textDetail from './text-detail.vue'
    import pdfDetail from './pdf-preview.vue'
    import audioDetail from './audio-preview.vue'
    import videoDetail from './video-preview.vue'
    import docDetail from './doc-preview.vue'
    import resDetail from './res-preview.vue'
    import resourceLinks from './resource-links.mixin';


    export default {

        mixins: [resourceLinks],

        props: {
            resource: {
                required: true,
                type: Object
            }
        },

        computed: {
            detailComponent() {
                return this.resource.type + '-detail';
            }
        },

        methods: {},


        components: {
            imageDetail,
            textDetail,
            pdfDetail,
            audioDetail,
            videoDetail,
            docDetail,
            resDetail
        }
    }
</script>

<style scoped>

    .resourceDetail {
    }

    .meta{
        color: grey;
        font-size: smaller;
    }

</style>