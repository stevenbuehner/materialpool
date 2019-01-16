<template>

    <b-image-lazy
            :src="resourceImagePreviewUrl"
            fluid
            :alt="resource.notes"
            center
            @click="goToResource"></b-image-lazy>

</template>

<script>

    import bImageLazy from 'bootstrap-vue/src/components/image/img-lazy';
    import bCard from 'bootstrap-vue/es/components/card/card'
    import bButton from 'bootstrap-vue/es/components/button/button'
    import {previewImageFirstPage} from '../../serverRoutes';
    import resourceLinks from '../resource-links.mixin';

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
                    default: 1024
                },
                height: {
                    required: false,
                    default: 1024
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
                return previewImageFirstPage(this.resource, this.width, this.height);
            },

        },

        methods: {
            goToResource() {
                window.location.href = this.resourceUrl;
            }
        },
        components: {
            bCard,
            bButton,
            bImageLazy
        }
    }
</script>

<style scoped>
    .myCard {
        max-width: 20rem;
        cursor: pointer;
    }
</style>