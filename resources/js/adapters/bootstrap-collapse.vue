<template>
  <div
      v-bind="$attrs"
      :id="id"
      class="collapse"
      :class="{'navbar-collapse': isNav, show: visible}"
  ><slot/></div>
</template>

<script>
export default {
    name: 'BCollapse',
    inheritAttrs: false,
    inject: {bootstrapNavbar: {default: null}},
    props: {
        id: {type: String, required: true},
        isNav: {type: Boolean, default: false},
    },
    computed: {
        visible() {
            return this.bootstrapNavbar?.collapseVisible(this.id) || false;
        },
    },
};
</script>
