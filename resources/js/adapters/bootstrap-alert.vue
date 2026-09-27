<template>
  <transition :name="fade ? 'fade' : ''">
    <div
        v-bind="$attrs"
        v-if="visible"
        class="alert"
        :class="[`alert-${variant}`, {'alert-dismissible': dismissible, show: fade}]"
        role="alert"
        aria-live="polite"
        aria-atomic="true"
    >
      <button
          v-if="dismissible"
          type="button"
          class="close"
          :aria-label="dismissLabel"
          @click="dismiss"
      >
        <slot name="dismiss"><span aria-hidden="true">&times;</span></slot>
      </button>
      <slot/>
    </div>
  </transition>
</template>

<script>
function countDownFrom(show) {
    if (show === '' || typeof show === 'boolean') {
        return 0;
    }

    const count = Number.parseInt(show, 10);
    return Number.isFinite(count) && count > 0 ? count : 0;
}

function visibleFrom(show) {
    return show === '' || show === true || countDownFrom(show) > 0;
}

export default {
    name: 'BAlert',
    inheritAttrs: false,

    props: {
        dismissLabel: {type: String, default: 'Close'},
        dismissible: {type: Boolean, default: false},
        fade: {type: Boolean, default: false},
        show: {type: [Boolean, Number, String], default: false},
        variant: {type: String, default: 'info'},
    },

    emits: ['dismissed', 'dismiss-count-down', 'input', 'update:modelValue'],

    data() {
        return {
            countDown: countDownFrom(this.show),
            visible: visibleFrom(this.show),
            timer: null,
        };
    },

    watch: {
        show(value) {
            this.start(value);
        },
    },

    mounted() {
        this.schedule();
    },

    beforeUnmount() {
        this.clearTimer();
    },

    methods: {
        start(value) {
            this.clearTimer();
            this.countDown = countDownFrom(value);
            this.visible = visibleFrom(value);
            this.schedule();
        },
        schedule() {
            if (this.countDown <= 0) {
                return;
            }

            this.$emit('dismiss-count-down', this.countDown);
            this.timer = window.setTimeout(() => {
                this.countDown -= 1;
                this.$emit('dismiss-count-down', this.countDown);
                this.$emit('input', this.countDown);
                this.$emit('update:modelValue', this.countDown);

                if (this.countDown > 0) {
                    this.schedule();
                } else {
                    this.finishDismissal();
                }
            }, 1000);
        },
        dismiss() {
            this.clearTimer();
            this.finishDismissal();
        },
        finishDismissal() {
            if (!this.visible) {
                return;
            }

            this.visible = false;
            this.$emit('input', false);
            this.$emit('update:modelValue', false);
            this.$emit('dismissed');
        },
        clearTimer() {
            if (this.timer !== null) {
                window.clearTimeout(this.timer);
                this.timer = null;
            }
        },
    },
};
</script>
