<template>
  <component
      :is="tag"
      v-bind="$attrs"
      class="badge"
      :class="[`text-bg-${variant}`, {'rounded-pill': pill}]"
  ><slot/></component>
</template>

<script>
export default {
    name: 'BBadge',
    inheritAttrs: false,
    props: {
        pill: {type: Boolean, default: false},
        tag: {type: String, default: 'span'},
        variant: {type: String, default: 'secondary'},
    },
};
</script>
