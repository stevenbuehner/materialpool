<template>
  <component :is="tag" v-bind="$attrs" class="list-group" :class="{'list-group-flush': flush, 'list-group-horizontal': horizontal}">
    <slot/>
  </component>
</template>
<script>
export default {
    name: 'BListGroup',
    inheritAttrs: false,
    props: {
        flush: {type: Boolean, default: false},
        horizontal: {type: Boolean, default: false},
        tag: {type: String, default: 'div'},
    },
};
</script>
