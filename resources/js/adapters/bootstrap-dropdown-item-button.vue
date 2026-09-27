<template>
  <li :class="$attrs.class" :style="$attrs.style" role="presentation">
    <button
        v-bind="buttonAttrs"
        type="button"
        class="dropdown-item"
        :class="{active}"
        :disabled="disabled"
        role="menuitem"
        @click="onClick"
    ><slot/></button>
  </li>
</template>

<script>
export default {
    name: 'BDropdownItemButton',
    inheritAttrs: false,
    inject: {bootstrapDropdown: {default: null}},
    props: {
        active: {type: Boolean, default: false},
        disabled: {type: Boolean, default: false},
    },
    emits: ['click'],
    computed: {
        buttonAttrs() {
            return Object.fromEntries(Object.entries(this.$attrs).filter(([key]) => !['class', 'style'].includes(key)));
        },
    },
    methods: {
        onClick(event) {
            this.$emit('click', event);
            this.$nextTick(() => this.bootstrapDropdown?.hide(true));
        },
    },
};
</script>
