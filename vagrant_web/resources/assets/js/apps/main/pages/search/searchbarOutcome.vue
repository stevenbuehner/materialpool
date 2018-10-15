<template>
    <div>
        <material-listing :materialIds="materialIds"></material-listing>
    </div>
</template>

<script>

    import MaterialListing from '../../../../components/Material/MaterialListing.vue';

    export default {

        props: {
            materialIds: {
                required: true,
                type: Array
            }
        },

        components: {
            MaterialListing
        }
    }
</script>

<style scoped>

</style>