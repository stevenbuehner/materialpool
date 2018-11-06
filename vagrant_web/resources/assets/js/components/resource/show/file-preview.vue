<template>
    <div class="card-header">
        No document preview available <span v-if="resource.original_filename">for <i>{{resource.original_filename}}</i></span>
    </div>
</template>

<script>
    export default {
        mixins: [],

        props: {
            resource: {
                required: true,
                type: Object
            }
        },
    }
</script>

<style scoped>

</style>