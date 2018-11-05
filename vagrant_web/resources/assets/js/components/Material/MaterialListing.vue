<template>
    <div class="material-listing">
        <div class="material-listing-menue"></div>
        <div class="material-listing-data">
            <material v-for="id in materialIds"
                      :id="id"
                      :key="id"
            ></material>
        </div>
    </div>
</template>

<script>
    import MaterialListingItem from './MaterialListingItem.vue';

    export default {
        mounted() {
        },

        props: ['materialIds'],

        components: {
            material: MaterialListingItem,
        }
    }
</script>

<style scoped>

</style>
