<template>
  <component
      :is="componentTag"
      v-bind="componentAttributes"
      class="btn"
      :class="buttonClasses"
      @click="onClick"
  >
    <slot/>
  </component>
</template>

<script>
export default {
    name: 'BButton',
    inheritAttrs: false,

    props: {
        block: {type: Boolean, default: false},
        disabled: {type: Boolean, default: false},
        href: {type: String, default: null},
        pill: {type: Boolean, default: false},
        pressed: {type: Boolean, default: null},
        size: {type: String, default: null},
        squared: {type: Boolean, default: false},
        tag: {type: String, default: 'button'},
        to: {type: [String, Object], default: null},
        type: {type: String, default: 'button'},
        variant: {type: String, default: 'secondary'},
    },

    emits: ['click', 'update:pressed'],

    computed: {
        isLink() {
            return this.to !== null || this.href !== null || this.tag === 'a';
        },
        componentTag() {
            if (this.to !== null) {
                return 'router-link';
            }

            return this.isLink ? 'a' : this.tag;
        },
        componentAttributes() {
            return {
                ...this.$attrs,
                ...(this.to !== null ? {to: this.to} : {}),
                ...(this.href !== null ? {href: this.href} : {}),
                ...(!this.isLink && this.tag === 'button' ? {type: this.type, disabled: this.disabled} : {}),
                ...(this.isLink && this.disabled ? {'aria-disabled': 'true', tabindex: '-1'} : {}),
                ...(this.pressed !== null ? {'aria-pressed': String(this.pressed), autocomplete: 'off'} : {}),
            };
        },
        buttonClasses() {
            return [
                `btn-${this.variant || 'secondary'}`,
                {
                    [`btn-${this.size}`]: this.size,
                    'btn-block': this.block,
                    'rounded-pill': this.pill,
                    'rounded-0': this.squared && !this.pill,
                    disabled: this.disabled && this.isLink,
                    active: this.pressed === true,
                },
            ];
        },
    },

    methods: {
        focus() {
            this.$el.focus();
        },
        blur() {
            this.$el.blur();
        },
        onClick(event) {
            if (this.disabled) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }

            if (this.pressed !== null) {
                this.$emit('update:pressed', !this.pressed);
            }
            this.$emit('click', event);
        },
    },
};
</script>
