<template>
    <div>
        <material-listing :materials="materials"></material-listing>
    </div>
</template>

<script>

    import MaterialListing from './../Material/MaterialListing.vue';

    export default {

        props: {
            materials: {
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