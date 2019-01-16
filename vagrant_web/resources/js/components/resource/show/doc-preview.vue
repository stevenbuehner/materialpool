<template>
    <div class="card-header">
        <span v-if="resource.original_filename"><i>{{resource.original_filename}}</i></span>
        <span v-else>No document preview available</span>
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