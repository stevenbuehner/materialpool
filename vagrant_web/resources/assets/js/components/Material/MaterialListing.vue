<template>
    <div class="material-listing">
        <div class="material-listing-menue"></div>
        <div class="material-listing-data">
            <material v-for="material in materials"
                      :material="material"
                      :key="material.id"
            ></material>
        </div>
    </div>
</template>

<script>
    import MaterialListingItem from './MaterialListingItem.vue';

    export default {
        mounted() {
            console.log('Material-Listing component mounted.')
        },

        props: ['materials'],

        components: {
            material: MaterialListingItem,
        }
    }
</script>

<style scoped>

</style>
