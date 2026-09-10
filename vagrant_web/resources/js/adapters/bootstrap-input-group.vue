<template>
  <component :is="tag" v-bind="$attrs" class="input-group" :class="size ? `input-group-${size}` : null" role="group">
    <bootstrap-input-group-prepend v-if="$slots.prepend || prepend">
      <slot name="prepend"><bootstrap-input-group-text>{{ prepend }}</bootstrap-input-group-text></slot>
    </bootstrap-input-group-prepend>
    <slot/>
    <bootstrap-input-group-append v-if="$slots.append || append">
      <slot name="append"><bootstrap-input-group-text>{{ append }}</bootstrap-input-group-text></slot>
    </bootstrap-input-group-append>
  </component>
</template>

<script>
import BootstrapInputGroupAppend from './bootstrap-input-group-append.vue';
import BootstrapInputGroupPrepend from './bootstrap-input-group-prepend.vue';
import BootstrapInputGroupText from './bootstrap-input-group-text.vue';

export default {
    name: 'BInputGroup',
    components: {BootstrapInputGroupAppend, BootstrapInputGroupPrepend, BootstrapInputGroupText},
    inheritAttrs: false,
    props: {
        append: {type: String, default: null},
        prepend: {type: String, default: null},
        size: {type: String, default: null},
        tag: {type: String, default: 'div'},
    },
};
</script>
