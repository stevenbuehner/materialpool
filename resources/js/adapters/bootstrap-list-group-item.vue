<template>
  <component
      :is="componentTag"
      v-bind="componentAttributes"
      class="list-group-item"
      :class="itemClasses"
      @click="onClick"
  ><slot/></component>
</template>
<script>
export default {
    name: 'BListGroupItem',
    inheritAttrs: false,
    props: {
        active: {type: Boolean, default: false},
        button: {type: Boolean, default: false},
        disabled: {type: Boolean, default: false},
        href: {type: String, default: null},
        tag: {type: String, default: 'div'},
        to: {type: [String, Object], default: null},
        variant: {type: String, default: null},
    },
    emits: ['click'],
    computed: {
        componentTag() {
            if (this.to !== null) return 'router-link';
            if (this.href !== null) return 'a';
            if (this.button) return 'button';
            return this.tag;
        },
        componentAttributes() {
            return {
                ...this.$attrs,
                ...(this.to !== null ? {to: this.to} : {}),
                ...(this.href !== null ? {href: this.href} : {}),
                ...(this.button ? {type: 'button', disabled: this.disabled} : {}),
                ...(!this.button && this.disabled ? {'aria-disabled': 'true', tabindex: '-1'} : {}),
            };
        },
        itemClasses() {
            return {
                active: this.active,
                disabled: this.disabled,
                'list-group-item-action': this.button || this.href !== null || this.to !== null,
                [`list-group-item-${this.variant}`]: this.variant,
            };
        },
    },
    methods: {
        onClick(event) {
            if (this.disabled) {
                event.preventDefault();
                return;
            }
            this.$emit('click', event);
        },
    },
};
</script>
