<template>

  <custom-dialog
      ref="dialog"
      :options="{
        yesEnabled: true,
        yesText: $t('pool.Save'),
        noEnabled: false,
        cancelEnabled: true,
        focusOn: 'none'
    }">

    <template v-slot:modal-title>{{ $t('pool.select-relevance') }}</template>

    <div class="form">
      <div class="row">
        <div class="col-sm-3">
          <label for="relevance">{{ $t('pool.relevance') }}:</label>
        </div>
        <div class="col-sm-9">
          <b-form-input
              name="relevance"
              id="relevance"
              type="range"
              v-model="range"
              autofocus
              min="0"
              :max="rangeMax">
          </b-form-input>

          {{
            $tc('pool.Set-relevance-for-xy-tags', numTags, {xy: numTags})
          }}:
          <span>{{ range }}</span>
        </div>

      </div>
    </div>

  </custom-dialog>

</template>

<script>
import CustomDialog                             from "./customDialog.vue";
import {RELEVANCE_USER_AVG, RELEVANCE_USER_MAX} from "../../../apps/config";
import {BFormInput}                             from "@/adapters/bootstrap";

export default {
  name: "relevanceSelector",

  props: {
    numTags: {
      type: Number,
      required: true,
      default: 0
    }
  },

  data() {
    return {
      rangeMax: RELEVANCE_USER_MAX,

      range: RELEVANCE_USER_AVG,

      dialogOptions: {
        yesEnabled: true,
        noEnabled: false,
        cancelEnabled: true
      }
    };
  },

  methods: {
    async show(options) {
      const response = await this.$refs.dialog.show(options);
      return {response, relevance: this.range};
    },

    hide() {
      return this.$refs.dialog.hide();
    },
  },

  components: {
    BFormInput,
    CustomDialog,
  }
}
</script>

<style scoped>

</style>
