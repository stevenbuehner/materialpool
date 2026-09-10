<template>
  <img
      v-bind="$attrs"
      :class="imageClasses"
      :src="src"
      :alt="alt"
      :width="width"
      :height="height"
      :loading="lazy ? 'lazy' : null"
  >
</template>
<script>
export default {
    name: 'BImg',
    inheritAttrs: false,
    props: {
        alt: {type: String, default: ''},
        blank: {type: Boolean, default: false},
        center: {type: Boolean, default: false},
        fluid: {type: Boolean, default: false},
        fluidGrow: {type: Boolean, default: false},
        height: {type: [Number, String], default: null},
        lazy: {type: Boolean, default: false},
        rounded: {type: [Boolean, String], default: false},
        src: {type: String, default: null},
        thumbnail: {type: Boolean, default: false},
        width: {type: [Number, String], default: null},
    },
    computed: {
        imageClasses() {
            return {
                'd-block': this.center,
                'img-fluid': this.fluid,
                'w-100': this.fluidGrow,
                'mx-auto': this.center,
                'img-thumbnail': this.thumbnail,
                rounded: this.rounded === true || this.rounded === '',
                [`rounded-${this.rounded}`]: typeof this.rounded === 'string' && this.rounded !== '',
            };
        },
    },
};
</script>
