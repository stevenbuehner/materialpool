<template>
  <li role="presentation"><form v-bind="$attrs" class="b-dropdown-form" tabindex="-1" @submit="$emit('submit', $event)"><slot/></form></li>
</template>

<script>
export default {
    name: 'BDropdownForm',
    inheritAttrs: false,
    emits: ['submit'],
};
</script>
