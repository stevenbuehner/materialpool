<template>
  <div class="material-listing">
    <div class="material-listing-menue"></div>
    <div class="material-listing-data">
      <material v-for="id in materialIds"
                :id="id"
                :key="id"
                :highlight-keywords="highlightKeywords"
                :highlight-bibleverses="highlightBibleverses"
      />
    </div>
  </div>
</template>

<script>
import MaterialListingItem from './MaterialListingItem.vue';

export default {
  mounted() {
  },

  props: {
    materialIds: {
      type: Array,
      required: true,
    },

    highlightKeywords: {
      type: Array,
      required: false,
      default() {
        return [];
      }
    },

    highlightBibleverses: {
      type: Array,
      required: false,
      default() {
        return [];
      }
    },
  },

  components: {
    material: MaterialListingItem,
  }
}
</script>

<style scoped>

</style>
