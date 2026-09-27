<template>
  <input
      v-bind="$attrs"
      ref="input"
      :value="localValue"
      :type="type"
      :class="controlClasses"
      :disabled="disabled"
      :readonly="readonly || plaintext"
      :aria-invalid="state === false ? 'true' : null"
      @input="onInput"
      @change="onChange"
      @blur="onBlur"
  >
</template>

<script>
import {debounceMilliseconds, formControlClasses} from './bootstrap-form-control';

export default {
    name: 'BFormInput',
    inheritAttrs: false,

    props: {
        debounce: {type: [Number, String], default: 0},
        disabled: {type: Boolean, default: false},
        modelValue: {default: undefined},
        plaintext: {type: Boolean, default: false},
        readonly: {type: Boolean, default: false},
        size: {type: String, default: null},
        state: {type: Boolean, default: null},
        type: {type: String, default: 'text'},
        value: {default: ''},
    },

    emits: ['blur', 'change', 'input', 'update:modelValue'],

    data() {
        return {
            localValue: this.currentValue(),
            inputTimer: null,
        };
    },

    computed: {
        controlClasses() {
            return formControlClasses(this);
        },
    },

    watch: {
        modelValue(value) {
            if (value !== undefined) {
                this.localValue = value;
            }
        },
        value(value) {
            if (this.modelValue === undefined) {
                this.localValue = value;
            }
        },
    },

    beforeUnmount() {
        this.clearTimer();
    },

    methods: {
        currentValue() {
            return this.modelValue === undefined ? this.value : this.modelValue;
        },
        focus() {
            this.$refs.input.focus();
        },
        blur() {
            this.$refs.input.blur();
        },
        select() {
            this.$refs.input.select();
        },
        onInput(event) {
            this.localValue = event.target.value;
            this.clearTimer();

            const delay = debounceMilliseconds(this.debounce);
            if (delay > 0) {
                this.inputTimer = window.setTimeout(() => this.emitValue(this.localValue), delay);
            } else {
                this.emitValue(this.localValue);
            }
        },
        onChange(event) {
            this.$emit('change', event.target.value);
        },
        onBlur(event) {
            this.$emit('blur', event);
        },
        emitValue(value) {
            this.inputTimer = null;
            this.$emit('input', value);
            this.$emit('update:modelValue', value);
        },
        clearTimer() {
            if (this.inputTimer !== null) {
                window.clearTimeout(this.inputTimer);
                this.inputTimer = null;
            }
        },
    },
};
</script>
