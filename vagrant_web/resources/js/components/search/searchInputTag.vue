<template>
  <div class="sb-search-input-tag" @click="isSelectable ? $emit('select'): true"
       :class="{'is-selectable':isSelectable, disabled}">

    <slot name="icon">
      <template v-if="iconPreloaded !== false">
        <component :is="iconPreloaded" class="icon"/>
      </template>
      <span v-else class="icon" :style="style"/>
    </slot>

    <slot>
      {{ text }}
    </slot>

    <span v-if="descendants.length > 0" class="descendants">({{
        descendants.map(kw => kw.title).join(', ')
      }})</span>

    <b-badge pill variant="secondary" v-if="showCrossRefs && crossReferencesCount !== false" class="cross-refs">
      {{ crossReferencesCount }}
    </b-badge>

    <slot name="before-button"></slot>

    <button v-if="showDisable && !disabled" @click.stop="$emit('deselect', item)"
            type="button"
            class="vs__deselect"
            aria-label="Remove option">
      <span aria-hidden="true"><slot name="close">&times;</slot></span>
    </button>

    <slot name="after-button"></slot>

  </div>
</template>

<script>

import searchInputTag_include                                from "./searchInputTag_include";
import {ayceIcon, bibleIcon, keyIcon, personIcon, placeIcon} from "../keyword/keywordDefaultIcons";
import {BBadge}                                              from 'bootstrap-vue'

export default {
  name: "searchInputTag",
  comments: {},
  mixins: [searchInputTag_include],

  props: {
    showDisable: {
      type: Boolean,
      default: true
    },

    showCrossRefs: {
      type: Boolean,
      default: false
    },

    isSelectable: {
      type: Boolean,
      default: false,
    }

  },

  computed: {
    type() {
      return this?.item?.type;
    },


    iconPreloaded() {
      if (this.type === 'b') {
        return bibleIcon;
      } else if (this.type === '*') {
        return ayceIcon;
      } else if (this.type === 'k') {

        switch (this.icon) {
          case null:
          case '/img/icons/tag.svg':
            return keyIcon;
          case '/img/icons/place.svg':
            return placeIcon;
          case '/img/icons/person.svg':
            return personIcon;
        }

      }

      return false;
    },

    style() {
      return {
        backgroundImage: 'url(' + this.icon + ')'
      };
    }

  },

  asyncComputed: {
    crossReferencesCount: {
      get() {
        if (this.type !== 'b') {
          return false;
        }

        return this.$store.dispatch('bibleversecrossreferences/getCount', {
          from: this?.item?.from,
          to: this?.item?.from
        });

      },
      default: false,
      lazy: true
    }
  },

  components: {BBadge}


}
</script>

<style lang="scss">

@import "../../../sass/theme";

.sb-search-input-tag {

  align-items: center;
  display: inline-flex;
  background-color: $vs-selected-bg;
  border: $vs-selected-border-width $vs-selected-border-style $vs-selected-border-color;
  border-radius: $vs-border-radius;
  line-height: $vs-component-line-height;
  margin: 4px 2px 0 2px;
  padding: 0 0.25em;
  z-index: 0;

  &.is-selectable {
    cursor: pointer;
  }

  &.disabled {
    background-color: $vs-state-disabled-bg;
    color: $vs-state-disabled-color;
    cursor: $vs-state-disabled-cursor;

    .icon path {
      stroke: $vs-state-disabled-color;
      fill: $vs-state-disabled-color;
    }
  }

  .icon {
    display: inline-block;
    background-size: contain;
    height: 1em;
    background-repeat: no-repeat;
    width: 1em;
    margin: 0 .25em 0 0;
  }

  .descendants {
    font-size: 0.8em;
    padding: 0.2em 0.2em 0 0.2em;
  }

  .search-more {
    margin: 0 .2em 0 .5em;
  }

  .cross-refs {
    margin: 0 .25em;
  }

}
</style>