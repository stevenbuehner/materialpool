<template>
  <span class="spinner-border materialpool-spinner"
        :class="spinnerClasses"
        role="status">
    <span class="visually-hidden">{{ label || $t('pool.Loading') }}</span>
  </span>
</template>

<script>
export default {
  name: 'MaterialpoolSpinner',

  props: {
    size: {
      type: String,
      default: 'md',
      validator: value => ['sm', 'md', 'lg'].includes(value)
    },

    variant: {
      type: String,
      default: 'primary'
    },

    label: {
      type: String,
      default: ''
    }
  },

  computed: {
    spinnerClasses() {
      return {
        'spinner-border-sm': this.size === 'sm',
        'materialpool-spinner--lg': this.size === 'lg',
        [`text-${this.variant}`]: true
      };
    }
  }
}
</script>

<style lang="scss">
.materialpool-spinner {
  display: inline-flex;

  &--lg {
    --bs-spinner-width: 2.5rem;
    --bs-spinner-height: 2.5rem;
    --bs-spinner-border-width: .25rem;
  }
}
</style>
