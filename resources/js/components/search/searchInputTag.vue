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

    <span v-if="Array.isArray(descendants) && descendants.length > 0" class="descendants">({{
        descendants.map(kw => kw.title).join(', ')
      }})</span>
    <span v-else-if="Number.isInteger(descendants) && descendants > 0" class="descendants">({{
        $tc('pool.xy-subtopics', descendants, {xy: descendants})
      }})</span>

    <b-badge pill variant="secondary" v-if="showCrossRefs && crossReferencesCount !== false" class="cross-refs">
      {{ crossReferencesCount }}
    </b-badge>

    <slot name="before-button"></slot>

    <button v-if="showDisable && !disabled" @click.stop="$emit('deselect', item)"
            type="button"
            class="search-input-tag-remove"
            aria-label="Remove option">
      <span aria-hidden="true"><slot name="close">&times;</slot></span>
    </button>

    <slot name="after-button"></slot>

  </div>
</template>

<script>

import searchInputTag_include from "./searchInputTag_include";
import {preloadedIcon}        from "../keyword/keywordDefaultIcons";
import {BBadge}               from '@/adapters/bootstrap'
import {useBibleverseCrossReferencesStore} from '../../apps/main/stores/bibleverseCrossReferences';
import {useKeywordSuggestionsStore}        from '../../apps/main/stores/keywordSuggestions';

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
      return preloadedIcon(this.icon);
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
        if (this.type === 'b') {
          return useBibleverseCrossReferencesStore().getCount({
            from: this?.item?.from,
            to: this?.item?.from
          });
        } else if (this.type === 'k') {
          return useKeywordSuggestionsStore().getCount(this?.item?.id);
        }

        return false;

      },
      default: false,
      lazy: true
    }
  },

  components: {BBadge}


}
</script>

<style lang="scss">

@use "../../../sass/theme" as *;

.sb-search-input-tag {

  align-items: center;
  display: inline-flex;
  background-color: $sidebar-tag-background-color-active;
  border: $border-width solid rgba($sidebar-input-font-color-active, .26);
  border-radius: $border-radius;
  color: $sidebar-input-font-color-active;
  line-height: 1.4;
  margin: 4px 2px 0 2px;
  padding: 0 0.25em;
  z-index: 0;

  &.is-selectable {
    cursor: pointer;

    &:hover {
      background-color: $sidebar-tag-background-color-active-hover;
    }
  }

  &.disabled {
    background-color: $sidebar-tag-background-color-disabled;
    color: $sidebar-input-font-color-disabled;
    cursor: not-allowed;

    .icon path {
      stroke: $sidebar-input-font-color-disabled;
      fill: $sidebar-input-font-color-disabled;
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

  button.search-input-tag-remove {
    appearance: none;
    background: none;
    border: 0;
    color: $sidebar-input-font-color-active-hover;
    cursor: pointer;
    display: inline-flex;
    margin-left: 4px;
    padding: 0;
    text-shadow: 0 1px 0 $white;

    &:hover,
    &:focus-visible {
      color: $sidebar-input-font-color-active;
    }
  }

}
</style>
