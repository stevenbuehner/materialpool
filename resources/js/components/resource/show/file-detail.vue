<template>
  <div>
    <span v-if="resource.original_filename"><i>{{ resource.original_filename }}</i></span>
    <span v-else>No file preview available</span>
  </div>
</template>

<script>


export default {
  name: 'FileDetail',

  mixins: [],

  props: {
    resource: {
      required: true,
      type: Object
    }
  },

  components: {}

}
</script>

<style scoped>

</style>