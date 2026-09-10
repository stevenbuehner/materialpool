<template>
  <li v-bind="$attrs" role="presentation">
    <header v-if="header" class="dropdown-header">{{ header }}</header>
    <ul class="list-unstyled" role="group"><slot/></ul>
  </li>
</template>

<script>
export default {
    name: 'BDropdownGroup',
    inheritAttrs: false,
    props: {header: {type: String, default: ''}},
};
</script>
