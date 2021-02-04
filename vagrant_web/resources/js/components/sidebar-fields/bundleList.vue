<template>
  <div class="sideBarField bundleListSidebarField">

    <div class="label">
      <slot name="label">
        <slot name="icon">
          <extension-icon/>
        </slot>

        <span class="title">
                    <slot name="title">{{ name }}</slot>
                </span>
      </slot>
    </div>

    <div class="editField" :class="{disabled}">
      <ol class="content-area">
        <li v-for="bundle in bundles">
          {{ bundle.name }}
        </li>
      </ol>
    </div>
  </div>
</template>

<script>
import generalMixin  from './generalSidebarFields.mixin';
import extensionIcon from 'svg-icon/dist/svg/material/extension.svg';


export default {
  name: "bundleList",

  mixins: [generalMixin],

  props: {},

  watch: {},

  data() {
    return {};
  },

  computed: {
    hasBundleIds() {
      return this.value && Array.isArray(this.value) && this.value.length > 0
    }
  },

  asyncComputed: {
    bundles: {
      get() {
        if (this.hasBundleIds) {
          const bundlePromiseArray = this.value.map((bundleId) => {
                return this.$store.dispatch('bundles/getBundleById', bundleId);
              }
          );

          return Promise.all(bundlePromiseArray);
        }

        return [];
      },
      default: []
    },
  },

  methods: {},

  components: {
    extensionIcon,
  }
}
</script>

<style lang="scss">
@import "resources/sass/theme";

.bundleListSidebarField {

  .editField {

    ol {
      border: $input-border-width solid $input-border-color;
      color: $sidebar-input-font-color-disabled;
      background-color: $sidebar-input-background-colour-active;
      padding: $input-padding-y-sm $input-padding-x-sm;
      @include border-radius($input-border-radius-sm);

      list-style-type: decimal;
      list-style-position: inside;

      li{
        padding: 0;
        margin: 0;
      }
    }

    &.disabled {
      ol {
        background-color: $sidebar-input-background-colour-disabled;
      }
    }

  }

}


</style>