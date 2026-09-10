<template><form v-bind="$attrs" class="form-inline" @submit="$emit('submit', $event)"><slot/></form></template>

<script>
export default {name: 'BNavForm', inheritAttrs: false, emits: ['submit']};
</script>
