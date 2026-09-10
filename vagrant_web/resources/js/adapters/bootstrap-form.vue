<template>
  <form v-bind="$attrs" @submit="$emit('submit', $event)" @reset="$emit('reset', $event)">
    <slot/>
  </form>
</template>

<script>
export default {
    name: 'BForm',
    inheritAttrs: false,
    emits: ['submit', 'reset'],
};
</script>
