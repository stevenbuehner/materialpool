<template>
  <textarea
      v-bind="$attrs"
      ref="input"
      :value="currentValue"
      :rows="rows"
      :class="controlClasses"
      :disabled="disabled"
      :readonly="readonly || plaintext"
      :aria-invalid="state === false ? 'true' : null"
      @input="onInput"
      @change="$emit('change', $event.target.value)"
      @blur="$emit('blur', $event)"
  />
</template>

<script>
import {formControlClasses} from './bootstrap-form-control';

export default {
    name: 'BFormTextarea',
    inheritAttrs: false,

    props: {
        disabled: {type: Boolean, default: false},
        maxRows: {type: [Number, String], default: null},
        modelValue: {default: undefined},
        plaintext: {type: Boolean, default: false},
        readonly: {type: Boolean, default: false},
        rows: {type: [Number, String], default: 2},
        size: {type: String, default: null},
        state: {type: Boolean, default: null},
        value: {default: ''},
    },

    emits: ['blur', 'change', 'input', 'update:modelValue'],

    computed: {
        currentValue() {
            return this.modelValue === undefined ? this.value : this.modelValue;
        },
        controlClasses() {
            return formControlClasses(this);
        },
    },

    mounted() {
        this.resize();
    },

    updated() {
        this.resize();
    },

    methods: {
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
            const value = event.target.value;
            this.$emit('input', value);
            this.$emit('update:modelValue', value);
            this.$nextTick(this.resize);
        },
        resize() {
            if (!this.maxRows || !this.$refs.input) {
                return;
            }

            const textarea = this.$refs.input;
            const lineHeight = Number.parseFloat(window.getComputedStyle(textarea).lineHeight) || 24;
            const maximumHeight = lineHeight * Number.parseInt(this.maxRows, 10);
            textarea.style.height = 'auto';
            textarea.style.height = `${Math.min(textarea.scrollHeight, maximumHeight)}px`;
        },
    },
};
</script>
