<template>
  <div v-bind="$attrs" class="progress" :style="height ? {height} : null">
    <slot/>
  </div>
</template>
<script>
export default {
    name: 'BProgress',
    inheritAttrs: false,
    props: {
        animated: {type: Boolean, default: false},
        height: {type: String, default: null},
        max: {type: [Number, String], default: 100},
        precision: {type: [Number, String], default: 0},
        showProgress: {type: Boolean, default: false},
        showValue: {type: Boolean, default: false},
        striped: {type: Boolean, default: false},
        variant: {type: String, default: null},
    },
    provide() {
        return {bootstrapProgress: this};
    },
};
</script>
