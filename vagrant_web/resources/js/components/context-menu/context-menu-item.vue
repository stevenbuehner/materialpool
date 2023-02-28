<template>
  <component
      :is="mainComponent"
      class="menuItem"
      @click.stop="menuItemClicked"
      :class="{disabled: disabled, 'with-icon' : icon !== ''}"
      :to="to"
      tabindex="0">
    <span class="icon" v-if="icon !== ''" :style="{backgroundImage : 'url(' + icon + ')'}"/>
    <slot :optional-data="optionalData"></slot>
  </component>
</template>

<script>
import {MENU_ITEM_CLICKED} from "./context-menu";

export default {
  name: "context-menu-item",

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

        if (!value.hasOwnProperty('name')) {
          console.error('Link misses "name" attribute in to property');
          return false;
        }

        return true;
      }
    },
  },

  methods: {
    menuItemClicked(event) {

      this.$parent.$emit(MENU_ITEM_CLICKED, this);
      this.$emit('click', event);

    }
  },

  computed: {
    isLink() {
      return this.to !== false;
    },

    mainComponent() {
      return this.isLink ? 'router-link' : 'li';
    }

  },


}
</script>

<style scoped>
.menuItem {
  border-bottom: 1px solid #E0E0E0;
  margin: 0;
  padding: 0.5em;
  line-height: 1em;
  color: black;
  text-decoration: none;
  display: list-item;
}

.with-icon {
  background-repeat: no-repeat;
  background-position: 0.5em 0.3em;
  background-size: 1.5em;
  padding-left: 2em;
}

.menuItem:last-child {
  border-bottom: none;
}

.menuItem:hover {
  background-color: #1E88E5;
  color: #FAFAFA;
  cursor: pointer;
}

.menuItem.disabled {
  cursor: default;
  color: grey;
  background-color: lightgrey;
}


</style>