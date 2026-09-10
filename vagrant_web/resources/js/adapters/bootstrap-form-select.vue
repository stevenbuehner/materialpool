<template>
  <select
      ref="input"
      v-bind="$attrs"
      :value="currentValue"
      :class="selectClasses"
      :disabled="disabled"
      :multiple="multiple"
      :required="required"
      :size="computedSelectSize"
      :aria-invalid="state === false ? 'true' : null"
      @change="onChange"
  >
    <slot name="first"/>
    <option
        v-for="(option, index) in normalizedOptions"
        :key="index"
        :value="option.value"
        :disabled="option.disabled"
    >{{ option.text }}</option>
    <slot/>
  </select>
</template>

<script>
import {normalizeSelectOption} from './bootstrap-form-select';

export default {
    name: 'BFormSelect',
    inheritAttrs: false,

    props: {
        disabled: {type: Boolean, default: false},
        modelValue: {default: undefined},
        multiple: {type: Boolean, default: false},
        options: {type: Array, default: () => []},
        plain: {type: Boolean, default: false},
        required: {type: Boolean, default: false},
        selectSize: {type: Number, default: 0},
        size: {type: String, default: null},
        state: {type: Boolean, default: null},
        value: {default: undefined},
    },

    emits: ['change', 'input', 'update:modelValue'],

    computed: {
        currentValue() {
            return this.modelValue === undefined ? this.value : this.modelValue;
        },
        normalizedOptions() {
            return this.options.map(normalizeSelectOption);
        },
        computedSelectSize() {
            return !this.plain && this.selectSize === 0 ? null : this.selectSize;
        },
        selectClasses() {
            return [
                this.plain ? 'form-control' : 'form-select',
                this.size ? `${this.plain ? 'form-control' : 'form-select'}-${this.size}` : null,
                {'is-valid': this.state === true, 'is-invalid': this.state === false},
            ];
        },
    },

    methods: {
        focus() {
            this.$refs.input.focus();
        },
        blur() {
            this.$refs.input.blur();
        },
        onChange(event) {
            const values = Array.from(event.target.options)
                .filter(option => option.selected)
                .map(option => Object.prototype.hasOwnProperty.call(option, '_value') ? option._value : option.value);
            const value = this.multiple ? values : values[0];
            this.$emit('input', value);
            this.$emit('update:modelValue', value);
            this.$nextTick(() => this.$emit('change', value));
        },
    },
};
</script>
