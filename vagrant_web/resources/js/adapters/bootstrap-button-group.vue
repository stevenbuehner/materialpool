<template><div v-bind="$attrs" class="btn-group" :class="size ? `btn-group-${size}` : null" role="group"><slot/></div></template>

<script>
export default {
    name: 'BButtonGroup',
    inheritAttrs: false,
    props: {size: {type: String, default: null}},
};
</script>
