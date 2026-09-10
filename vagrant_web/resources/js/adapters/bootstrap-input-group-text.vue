<template><component :is="tag" v-bind="$attrs" class="input-group-text"><slot/></component></template>
<script>
export default {
    name: 'BInputGroupText',
    inheritAttrs: false,
    props: {tag: {type: String, default: 'div'}},
};
</script>
