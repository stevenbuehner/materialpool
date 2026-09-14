<template>
  <vue-select class="searchInputSelect"
              :multiple="true"
              :selectOnTab="true"
              :options="options"
              :placeholder="$t('pool.Insert-search-phrase-here')"
              :filterable="false"
              :model-value="lineValues"
              language="de-DE"
              label="text"
              @update:model-value="$emit('updated', $event)"
              @search="onSearch"
  >

    <template v-slot:no-options>
      {{ $t('pool.Insert-search-phrase') }}
    </template>

    <template v-slot:option="option">
      <div class="d-center">

        <template v-if="preloadedIcon(option.icon) !== false">
          <component :is="preloadedIcon(option.icon)" class="icon"/>
        </template>
        <span v-else class="icon" :style="{backgroundImage: 'url('+ option.icon+')'}"/>

        {{ option.text }}

        <span class="descendants" v-if="option.descendants && option.descendants > 0">
          ({{ $tc('pool.xy-subtopics', option.descendants, {xy: option.descendants}) }})
        </span>
        <button v-if="option.crossRefs && option.crossRefs >= 1">
          ({{ option.crossRefs }})
        </button>
      </div>
    </template>

    <template v-slot:selected-option-container="{option, disabled, deselect}">
      <search-input-tag v-bind="{...option, disabled}" @deselect="deselect(option)"/>
    </template>

  </vue-select>
</template>

<script>
import vueSelect       from '@/adapters/vue-select';
import _debounce       from 'lodash/debounce';
import SearchInputTag  from "./searchInputTag";
import {preloadedIcon} from "../keyword/keywordDefaultIcons";
import {useTagSearchStore} from '../../apps/main/stores/tagSearch';

export default {

  props: {
    lineValues: {
      type: Array,
      required: true,
    },

    showDescendants: {
      type: Boolean,
      required: false,
      default: true
    }
  },

  data() {
    return {
      options: [],
    };
  },

  watch: {},


  methods: {
    onSearch(search, loading) {
      loading(true);

      this.search(loading, search, this);
    },

    // _.debounce is a function provided by lodash to limit how
    // often a particularly expensive operation can be run.
    // To learn
    // more about the _.debounce function (and its cousin
    // _.throttle), visit: https://lodash.com/docs#debounce
    search: _debounce((loading, search, vm) => {

      useTagSearchStore().searchTags(search)
        .then((data) => {
          vm.options = data.data.filter((el) => {
            // Remove already displayed options

            // Wenn es ein Tag ist mit ID, dann verhindere eine doppelte Auswahl
            if (el?.item?.id) {
              const found = vm.lineValues.find(lvEl => lvEl?.item?.id === el?.item?.id);
              return found === undefined;
            }

            return true;
          });

          loading(false);
        });

    }, 250),

    preloadedIcon(icon) {
      return preloadedIcon(icon);
    }

  },

  created() {
  },

  components: {
    SearchInputTag,
    vueSelect
  },
}
</script>


<style lang="scss">
@use "resources/sass/theme" as *;
.searchInputSelect {

  .multiselect-tags {
    .selected .close {
      margin-left: 0.25rem;
      top: -.15rem;
      position: relative;
    }

  }

  img {
    height: auto;
    max-width: 2.5rem;
    margin-right: 1rem;
  }

  .d-center {
    align-items: center;
    display: inline-flex;
  }


  .multiselect-dropdown {

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
      padding: 0.2em 0 0 0.5em;
    }

    li {
      border-bottom: 1px solid rgba(112, 128, 144, 0.1);
    }

    li:last-child {
      border-bottom: none;
    }

    li a {
      padding: 10px 20px;
      width: 100%;
      font-size: 1.25em;
      color: #3c3c3c;
    }

    .active > a {
      color: green;
    }

  }

}
</style>
