<template>
  <div class="container-fluid">

    <h1>Keyword Detail</h1>

    <keyword-edit
        :id="id"
    ></keyword-edit>

  </div>
</template>

<script>
import KeywordEdit from "../../../components/keyword/keywordEdit";
import {useKeywordsStore} from '../stores/keywords';


export default {
  name: "KeywordDetail",
  components: {KeywordEdit},

  props: {
    id: {
      type: Number,
      required: true
    }
  },

  data() {
    return {
      forceReload: 0,
    }
  },

  asyncComputed: {
    keyword: {
      get() {
        return useKeywordsStore().get(this.id);
      },
      default: null,
      /* watch() {
                  this.forceReload
              }*/
    },

  },


}
</script>

<style scoped>

</style>
