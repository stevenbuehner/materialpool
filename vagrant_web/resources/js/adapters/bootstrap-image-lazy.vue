<template><bootstrap-image v-bind="$attrs" :lazy="true"/></template>
<script>
import BootstrapImage from './bootstrap-image.vue';

export default {
    name: 'BImgLazy',
    components: {BootstrapImage},
    inheritAttrs: false,
};
</script>
