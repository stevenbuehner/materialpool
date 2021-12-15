<template>
  <b-modal
      :title="$t('pool.Edit-Keyword')"
      :hide-footer="true"
      v-model="showEditor"
      v-if="showEditor"
  >
    <keyword-edit
        :id="keywordId"
        @saved="onSaved"
        @saving="$emit('saving', $event)"
        @savingError="$emit('savingError', $event)"
        @deleted="$emit('deleted', $event)">

      <template slot="additional-buttons">
        <b-button variant="secondary"
                  @click.prevent="hide">{{ $t('pool.cancel') }}
        </b-button>
      </template>

    </keyword-edit>

  </b-modal>
</template>

<script>
import {BButton, BModal} from 'bootstrap-vue'
import KeywordEdit       from "../../keyword/keywordEdit";


export default {
  name: "keywordEditor",

  props: {
    id: {
      type: Number,
      required: true
    },
  },

  data() {
    return {
      keywordId: this.id,
      showEditor: false
    }
  },


  computed: {},

  methods: {
    onSaved(keyword) {
      this.hide();
      this.$emit('saved', keyword);
    },

    show(keywordId) {

      if (keywordId) {
        this.keywordId = keywordId;
      } else {
        this.keywordId = this.id;
      }

      this.showEditor = true;
    },
    hide() {
      this.showEditor = false;
    }
  },


  components: {
    KeywordEdit,
    BModal,
    BButton
  }
}
</script>

<style scoped>

</style>