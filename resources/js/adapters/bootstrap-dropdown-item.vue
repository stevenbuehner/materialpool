<template>
  <li :class="$attrs.class" :style="$attrs.style" role="presentation">
    <router-link
        v-if="to"
        v-bind="linkAttrs"
        :to="to"
        class="dropdown-item"
        :class="{active, disabled}"
        :aria-disabled="disabled ? 'true' : null"
        role="menuitem"
        @click="onClick"
    ><slot/></router-link>
    <a
        v-else
        v-bind="linkAttrs"
        :href="href || '#'"
        class="dropdown-item"
        :class="{active, disabled}"
        :aria-disabled="disabled ? 'true' : null"
        role="menuitem"
        @click="onClick"
    ><slot/></a>
  </li>
</template>

<script>
export default {
    name: 'BDropdownItem',
    inheritAttrs: false,
    inject: {bootstrapDropdown: {default: null}},
    props: {
        active: {type: Boolean, default: false},
        disabled: {type: Boolean, default: false},
        href: {type: String, default: null},
        to: {type: [String, Object], default: null},
    },
    emits: ['click'],
    computed: {
        linkAttrs() {
            return Object.fromEntries(Object.entries(this.$attrs).filter(([key]) => !['class', 'style'].includes(key)));
        },
    },
    methods: {
        onClick(event) {
            if (this.disabled) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return;
            }
            if (!this.to && !this.href) event.preventDefault();
            this.$emit('click', event);
            this.$nextTick(() => this.bootstrapDropdown?.hide(true));
        },
    },
};
</script>
