<template>
  <bootstrap-dropdown v-bind="$attrs" nav><template #button-content><slot name="button-content">{{ text }}</slot></template><slot/></bootstrap-dropdown>
</template>

<script>
import BootstrapDropdown from './bootstrap-dropdown.vue';

export default {
    name: 'BNavItemDropdown',
    inheritAttrs: false,
    components: {BootstrapDropdown},
    props: {text: {type: String, default: ''}},
};
</script>
