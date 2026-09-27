<template>
  <li v-bind="itemAttrs" class="nav-item" :class="{disabled}">
    <router-link
        v-if="to"
        :to="to"
        class="nav-link"
        :class="{active, disabled}"
        :aria-disabled="disabled ? 'true' : null"
        @click="onClick"
    ><slot/></router-link>
    <a
        v-else
        :href="href || '#'"
        class="nav-link"
        :class="{active, disabled}"
        :aria-disabled="disabled ? 'true' : null"
        @click="onClick"
    ><slot/></a>
  </li>
</template>

<script>
export default {
    name: 'BNavItem',
    inheritAttrs: false,
    props: {
        active: {type: Boolean, default: false},
        disabled: {type: Boolean, default: false},
        href: {type: String, default: null},
        to: {type: [String, Object], default: null},
    },
    emits: ['click'],
    computed: {
        itemAttrs() {
            return this.$attrs;
        },
    },
    methods: {
        onClick(event) {
            if (this.disabled) {
                event.preventDefault();
                return;
            }
            if (!this.to && !this.href) event.preventDefault();
            this.$emit('click', event);
        },
    },
};
</script>
