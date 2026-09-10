<template><component :is="tag" v-bind="$attrs" class="input-group-append"><slot/></component></template>
<script>
export default {
    name: 'BInputGroupAppend',
    inheritAttrs: false,
    props: {tag: {type: String, default: 'div'}},
};
</script>
