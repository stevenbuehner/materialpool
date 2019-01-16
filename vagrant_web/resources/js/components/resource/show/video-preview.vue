<template>
    <div class="card-header">
        <video
                class="sbVideo "
                controls preload="auto"
                data-setup='{"fluid": true}'
                :poster="posterRoute">

            <source :src="videoRoute" :type="videoMimeType"/>
        </video>
    </div>
</template>

<script>

    import {poolResourceVideostream, previewImageFirstPage} from "../../serverRoutes";

    export default {
        mixins: [],

        props: {
            resource: {
                required: true,
                type: Object
            },
        },


        computed: {
            posterRoute() {
                return previewImageFirstPage(this.resource);
            },

            videoRoute() {
                return poolResourceVideostream(this.resource);
            },

            videoMimeType() {
                return this.resource.mime_type || 'video';
            }
        }


    }
</script>

<style scoped>
    .sbVideo {
        max-width: 100%;
    }
</style>