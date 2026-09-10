<template>
  <component :is="tag" v-bind="$attrs" class="input-group" :class="size ? `input-group-${size}` : null" role="group">
    <template v-if="$slots.prepend || prepend">
      <slot name="prepend"><bootstrap-input-group-text>{{ prepend }}</bootstrap-input-group-text></slot>
    </template>
    <slot/>
    <template v-if="$slots.append || append">
      <slot name="append"><bootstrap-input-group-text>{{ append }}</bootstrap-input-group-text></slot>
    </template>
  </component>
</template>

<script>
import BootstrapInputGroupText from './bootstrap-input-group-text.vue';

export default {
    name: 'BInputGroup',
    components: {BootstrapInputGroupText},
    inheritAttrs: false,
    props: {
        append: {type: String, default: null},
        prepend: {type: String, default: null},
        size: {type: String, default: null},
        tag: {type: String, default: 'div'},
    },
};
</script>
