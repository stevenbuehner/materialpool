<template>
    <div class="resource">
        <image-preview v-if="resource.previewable.image === true" :resource="resource"></image-preview>
        <html-preview v-else-if="resource.previewable.html === true" :resource="resource"></html-preview>
        <div v-else>
            NO PREVIEW
        </div>
    </div>
</template>

<script>
    import imagePreview from './image-preview.vue'
    import htmlPreview from './html-preview.vue'


    export default {
        name: "resource",
        props: {
            resource: {
                required: true,
                type: Object
            }
        },


        components: {
            imagePreview,
            htmlPreview
        }
    }
</script>

<style scoped>

    .resource{

    }

</style>