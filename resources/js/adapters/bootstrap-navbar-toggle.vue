<template>
  <button
      v-bind="$attrs"
      type="button"
      class="navbar-toggler"
      :aria-controls="target"
      :aria-expanded="expanded ? 'true' : 'false'"
      :aria-label="label"
      @click="bootstrapNavbar?.toggleCollapse(target)"
  ><slot><span class="navbar-toggler-icon"/></slot></button>
</template>

<script>
export default {
    name: 'BNavbarToggle',
    inheritAttrs: false,
    inject: {bootstrapNavbar: {default: null}},
    props: {
        label: {type: String, default: 'Toggle navigation'},
        target: {type: String, required: true},
    },
    computed: {
        expanded() {
            return this.bootstrapNavbar?.collapseVisible(this.target) || false;
        },
    },
};
</script>
