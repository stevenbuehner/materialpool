<template>
  <div class="container-fluid">
    <bundle v-for="b in bundles"
            :uuid="b.uuid"
            :key="b.id"></bundle>
  </div>
</template>

<script>
import Bundle       from "../../../components/bundles/bundle.vue";
import {useBundlesStore} from '../stores/bundles';


export default {
  name: "BundleOverview",

  data() {
    return {
      stillLoading: true,
      errorMessage: ''
    };
  },


  asyncComputed: {
    bundles: {
      get() {
        return useBundlesStore().allBundles();
      },
      default: null,
      /* watch() {
                  this.forceReload
              }*/
    }
  },

  created() {
  },

  components: {
    Bundle

  }


}
</script>

<style scoped>

</style>
