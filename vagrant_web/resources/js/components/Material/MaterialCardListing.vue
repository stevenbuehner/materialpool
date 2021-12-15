<template>
  <div class="material-listing">
    <div class="material-listing-menue"></div>

    <div class="card-columns" id="material-listing-data">
      <material v-for="material in materials"
                :material="material"
                :key="material.id"
      ></material>
    </div>
  </div>
</template>

<script>
import MaterialCardListingItem from './MaterialCardListingItem.vue';

export default {

  props: ['materials'],

  components: {
    material: MaterialCardListingItem,
  }
}
</script>

<style scoped>

</style>
