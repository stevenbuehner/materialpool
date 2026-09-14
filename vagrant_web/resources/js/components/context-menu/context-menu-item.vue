<template>
  <li role="presentation">
    <component
        :is="mainComponent"
        class="dropdown-item"
        :class="{disabled: disabled, 'with-icon' : icon !== ''}"
        :to="to"
        :type="isLink ? null : 'button'"
        :disabled="disabled"
        :aria-disabled="disabled ? 'true' : null"
        role="menuitem"
        @click.stop="menuItemClicked">
      <span class="icon" v-if="icon !== ''" :style="{backgroundImage : 'url(' + icon + ')'}"/>
      <slot :optional-data="optionalData"></slot>
    </component>
  </li>
</template>

<script>
export default {
  name: "context-menu-item",

  emits: ['click'],

  inject: {
    contextMenuItemClicked: {default: null},
  },

  props: {
    disabled: {
      required: false,
      type: Boolean,
      default: false
    },

    icon: {
      required: false,
      type: String,
      default: ''
    },

    optionalData: {
      required: false,
      default: null
    },


    to: {
      required: false,
      default: false,
      validator(value) {
        if (value === false) {
          return true;
        }

        if (!Object.hasOwn(value, 'name')) {
          console.error('Link misses "name" attribute in to property');
          return false;
        }

        return true;
      }
    },
  },

  methods: {
    menuItemClicked(event) {
      if (this.disabled) {
        event.preventDefault();
        return;
      }

      this.contextMenuItemClicked?.(this);
      this.$emit('click', event);

    }
  },

  computed: {
    isLink() {
      return this.to !== false;
    },

    mainComponent() {
      return this.isLink ? 'router-link' : 'button';
    }

  },


}
</script>

<style lang="scss" scoped>
@use "../../../sass/theme" as *;

.dropdown-item:hover,
.dropdown-item:focus {
  background-color: $gray-400;
}

.with-icon {
  background-repeat: no-repeat;
  background-position: 1rem center;
  background-size: 1.5em;
  padding-left: 3rem;
}

</style>
