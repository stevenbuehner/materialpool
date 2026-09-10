<template>
  <div class="form-check">
    <input
        v-bind="$attrs"
        :id="resolvedId"
        type="checkbox"
        class="form-check-input"
        :checked="currentValue"
        @change="onChange"
    >
    <label class="form-check-label" :for="resolvedId">
      <slot/>
    </label>
  </div>
</template>

<script>
let checkboxId = 0;

export default {
    name: 'BFormCheckbox',
    inheritAttrs: false,

    props: {
        id: {
            type: String,
            default: null,
        },
        checked: {
            type: Boolean,
            default: false,
        },
        modelValue: {
            type: Boolean,
            default: undefined,
        },
    },

    emits: ['input', 'update:modelValue', 'change'],

    data() {
        checkboxId += 1;

        return {
            generatedId: `materialpool-checkbox-${checkboxId}`,
        };
    },

    computed: {
        resolvedId() {
            return this.id || this.generatedId;
        },
        currentValue() {
            return this.modelValue === undefined ? this.checked : this.modelValue;
        },
    },

    methods: {
        onChange(event) {
            const value = event.target.checked;
            this.$emit('input', value);
            this.$emit('update:modelValue', value);
            this.$emit('change', value);
        },
    },
};
</script>
