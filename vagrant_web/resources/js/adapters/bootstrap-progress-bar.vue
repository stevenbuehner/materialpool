<template>
  <div
      v-bind="$attrs"
      class="progress-bar"
      :class="barClasses"
      role="progressbar"
      :style="{width: `${percentage}%`}"
      :aria-valuenow="value"
      aria-valuemin="0"
      :aria-valuemax="resolvedMax"
  >
    <slot>{{ label }}</slot>
  </div>
</template>
<script>
import {progressPercentage} from './bootstrap-progress';

export default {
    name: 'BProgressBar',
    inheritAttrs: false,
    inject: {bootstrapProgress: {default: null}},
    props: {
        animated: {type: Boolean, default: null},
        label: {type: String, default: null},
        max: {type: [Number, String], default: null},
        striped: {type: Boolean, default: null},
        value: {type: [Number, String], default: 0},
        variant: {type: String, default: null},
    },
    computed: {
        resolvedMax() {
            return this.max ?? this.bootstrapProgress?.max ?? 100;
        },
        resolvedVariant() {
            return this.variant ?? this.bootstrapProgress?.variant;
        },
        resolvedStriped() {
            return this.striped ?? this.bootstrapProgress?.striped ?? false;
        },
        resolvedAnimated() {
            return this.animated ?? this.bootstrapProgress?.animated ?? false;
        },
        percentage() {
            return progressPercentage(this.value, this.resolvedMax);
        },
        barClasses() {
            return {
                [`bg-${this.resolvedVariant}`]: this.resolvedVariant,
                'progress-bar-striped': this.resolvedStriped,
                'progress-bar-animated': this.resolvedAnimated,
            };
        },
    },
};
</script>
