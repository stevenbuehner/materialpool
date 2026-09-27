<template>
  <!-- eslint-disable vue/no-multiple-template-root -- Vue 3 fragment preserves Bootstrap's sibling feedback markup. -->
  <div
      v-if="invalidFeedback"
      :id="invalidFeedbackId"
      class="invalid-feedback"
      :class="{'d-block': state === false}"
      aria-live="assertive"
      tabindex="-1"
  >{{ invalidFeedback }}</div>
  <div
      v-if="validFeedback"
      :id="validFeedbackId"
      class="valid-feedback"
      :class="{'d-block': state === true}"
      aria-live="assertive"
      tabindex="-1"
  >{{ validFeedback }}</div>
  <small
      v-if="description"
      :id="descriptionId"
      class="form-text text-muted"
      tabindex="-1"
  >{{ description }}</small>
</template>

<script>
export default {
    name: 'BootstrapFormFeedback',
    props: {
        description: {type: String, default: null},
        descriptionId: {type: String, required: true},
        invalidFeedback: {type: String, default: null},
        invalidFeedbackId: {type: String, required: true},
        state: {type: Boolean, default: null},
        validFeedback: {type: String, default: null},
        validFeedbackId: {type: String, required: true},
    },
};
</script>
