<template><component :is="tag" v-bind="$attrs" class="input-group-prepend"><slot/></component></template>
<script>
export default {
    name: 'BInputGroupPrepend',
    inheritAttrs: false,
    props: {tag: {type: String, default: 'div'}},
};
</script>
