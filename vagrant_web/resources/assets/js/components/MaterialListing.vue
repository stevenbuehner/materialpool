<template>
    <div class="material-listing">
        <div class="material-listing-menue"></div>
        <div class="material-listing-data">
            <material v-for="material in materials" v-bind:material="material"></material>
        </div>
    </div>
</template>

<script>
    export default {
        mounted() {
            console.log('Material-Listing component mounted.')
        },
        props : ['materials']
    }
</script>

<style lang="css">

</style>
