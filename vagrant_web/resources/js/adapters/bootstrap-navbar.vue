<template><nav v-bind="$attrs" :class="classes"><slot/></nav></template>

<script>
import {navbarClasses} from './bootstrap-navbar';

export default {
    name: 'BNavbar',
    inheritAttrs: false,
    props: {
        fixed: {type: String, default: null},
        toggleable: {type: [Boolean, String], default: false},
        type: {type: String, default: null},
        variant: {type: String, default: null},
    },
    data() {
        return {visibleCollapses: {}};
    },
    provide() {
        return {bootstrapNavbar: this};
    },
    computed: {
        classes() {
            return navbarClasses(this);
        },
    },
    methods: {
        collapseVisible(id) {
            return Boolean(this.visibleCollapses[id]);
        },
        toggleCollapse(id) {
            this.visibleCollapses[id] = !this.collapseVisible(id);
        },
    },
};
</script>
