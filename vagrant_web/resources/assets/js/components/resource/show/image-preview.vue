<template>
    <b-card :title="title"
            :img-src="resourceImagePreviewUrl"
            img-alt="Preview Image"
            img-top
            tag="article"
            class="mb-2 myCard"
            @click="goToResource">

        <p class="card-text">
            {{resource.notes}}
        </p>

        <b-button :href="downloadResourceLink(resource)" variant="primary">{{$t('pool.download-file')}}</b-button>

    </b-card>

</template>

<script>

    import bCard from 'bootstrap-vue/es/components/card/card'
    import bButton from 'bootstrap-vue/es/components/button/button'
    import resourceLinks from './../resource-links.mixin';

    export default {

        mixins: [resourceLinks],

        props:
            {
                resource: {
                    required: true,
                    type: Object
                },
                width: {
                    required: false,
                    default: 300
                },
                height: {
                    required: false,
                    default: 300
                }
            },

        computed: {

            title() {
                var title = 'Resource';

                if (this.resource.original_filename) {
                    title = this.resource.original_filename;
                }

                return title
            },

            resourceImagePreviewUrl() {
                return '/resource/image/' + this.resource.id + '/' + this.width + '/' + this.height
            },

        },

        methods: {
            goToResource() {
                window.location.href = this.resourceUrl;
            }
        },
        components: {
            bCard,
            bButton
        }
    }
</script>

<style scoped>
    .myCard {
        max-width: 20rem;
        cursor: pointer;
    }
</style>