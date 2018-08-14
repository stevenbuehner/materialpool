<template>
    <div class="card-header">
        <div>{{resource.content | trim(300) }}</div>
    </div>
</template>

<script>
    import truncateFilter from './../filters/truncate-filter.mixin';

    export default {
        mixins: [
            truncateFilter,
        ],

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