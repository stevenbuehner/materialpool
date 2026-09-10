<template><form v-bind="$attrs" class="d-flex align-items-center" @submit="$emit('submit', $event)"><slot/></form></template>

<script>
export default {name: 'BNavForm', inheritAttrs: false, emits: ['submit']};
</script>
