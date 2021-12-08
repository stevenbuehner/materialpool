<template>
  <div ref="sideBarField" class="sideBarField tagEditSidebarField">

    <div class="label">
      <slot name="label">
        <slot name="icon">
          <tag-icon/>
        </slot>

        <span class="title">
                    <slot name="title">{{ name }}</slot>
                </span>
      </slot>
    </div>

    <div class="editField">

      <slot name="input">
        <vue-select
            :clearSearchOnSelect="clearAndCloseOnSelect"
            :close-on-select="clearAndCloseOnSelect"
            :disabled="disabled"
            :filterBy="filterSuggestionsBy"
            :filterable="true"
            :getOptionLabel="getTagLabelFromObject"
            :multiple="true"
            :options="suggestedFilteredTags"
            :placeholder="placeholder"
            :selectOnTab="true"
            :value="validTypesValues"
            @input="onInputChanged"
            @search="onSearch"
            @search:blur=""
            :class="{hasElements : validTypesValues.length > 0 }"
        >
          <template v-slot:selected-option-container="{option, disabled, multiple, deselect}">
            <dragable-element
                :id="option.id"
                :disable-move-relevance="disabled"
                :disable-remove-element="disabled"
                :label="getTagLabelFromObject(option)"
                :relevance="option.pivot.relevance"
                @deselect="deselect(option)"
                @single-click="$emit('request-info', option)"
                @request-update-relevance="$emit('request-update-relevance', {tag: option, relevance: $event})"
                @click:right="openRightClickMenu($event, option)"/>
          </template>

          <template v-slot:option="option">
            <span :class="{'is-new' : option.isNew}" class="suggested-option">
                <span class="tagOptionIcon">
                    <slot name="icon">
                        <tag-icon/>
                    </slot>
                </span>
                <span class="suggested-text">
                    {{ getTagLabelFromObject(option) }}
                </span>
                <span v-if="option.isNew" class="is-new badge badge-info">{{ $t('pool.new') }}</span>
            </span>
          </template>

          <template v-slot:no-options>{{ $t('pool.no-results') }}</template>

          <template v-slot:list-footer="options">
            Footer: {{ hasMoreResults ? 'hasMore' : 'done' }} - {{ options }}
          </template>

        </vue-select>
      </slot>

    </div>

    <context-menu ref="menu" v-slot:default="{optionalData}">
      <context-menu-item v-if="!disabled && !optionalData.from"
                         @click.stop="$refs.keywordEditor.show(optionalData.id)">
        {{ $t('pool.edit') }}
      </context-menu-item>
      <context-menu-item @click.stop="doToTagSearch(optionalData)">
        {{ $t('pool.search-for-xy', {xy: getTagLabelFromObject(optionalData)}) }}
      </context-menu-item>
      <context-menu-item v-if="optionalData.from && optionalData.to"
                         @click="displayBibleverse(optionalData.from, optionalData.to)">
        {{ $t('pool.Read-Bibleverse') }}
      </context-menu-item>
      <context-menu-item @click="copyTagContent(getTagLabelFromObject(optionalData))">
        {{ $t('pool.Copy') }}
      </context-menu-item>
    </context-menu>

    <keyword-editor
        :id="0"
        ref="keywordEditor"
        @saved="updateKeywordChanges"/>

    <bible-popover v-if="biblePopover.showMe" :bibleverse="biblePopover.bibleverse" :position="$refs.sideBarField"
                   @bible-popover-closerequest="biblePopover.showMe = false"/>
    <!--
     @deleted="onDeleted"/>
    @saved="onKeywordPropertiesChanged" -->

  </div>
</template>

<script>
import generalMixin                                           from './generalSidebarFields.mixin';
import VueSelect                                              from 'vue-select/dist/vue-select';
import tagIcon                                                from 'svg-icon/dist/svg/material/style.svg';
import {keywordTypes}                                         from "../keyword/keywordDefaultIcons";
import {debounce as _debounce, differenceBy as _differenceBy} from 'lodash';
import DragableElement                                        from "./vue-select/dragable-element";
import ContextMenu                                            from "../context-menu/context-menu";
import ContextMenuItem                                        from "../context-menu/context-menu-item";
import KeywordEditor                                          from "../modals/editors/keywordEditor";
import {searchArrayObjectsToSearchQuery}                      from "../search/searchHelper";

import BiblePopover            from "../bible-popover/bible-popover";
import BibleVerse              from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/in/BibleVerse.js';
import {copyStringToClipboard} from "../../helper/copyToClipboard";

export default {
  name: "tagEdit",

  mixins: [generalMixin],

  props: {
    typefilter: {
      type: String,
      required: false,
      default: '',
      validator(value) {
        return value === '' || keywordTypes.includes(value);
      }
    },

    value: {
      required: true,
      validator(value) {

        return typeof value === 'object';

      }
    },

    minInput: {
      type: Number,
      required: false,
      default: 3
    },

    newTagsEnabled: {
      type: Boolean,
      required: false,
      default: true
    },

  },

  data() {
    return {
      suggestedFilteredTags: [],
      clearOnSelect: true,

      // Pagination
      observer: null,
      searchString: '', // Wenn der Suchstring noch der selbe ist, wird die pageZahl erhöht
      hasMoreResults: false,
      page: 1,

      biblePopover: {
        showMe: false,
        bibleverse: null
      }
    };
  },

  computed: {
    validTypesValues() {
      // About bind: https://stackoverflow.com/questions/49714015/why-does-this-inside-filter-gets-undefined-in-vuejs
      return this.value.filter(function (el) {
            return el.type === this.typefilter || this.typefilter === '';
          }.bind(this)
      );
    },

    invalidTypesValues() {
      return this.value.filter(function (el) {
            return el.type !== this.typefilter && this.typefilter !== '';
          }.bind(this)
      );
    },

    clearAndCloseOnSelect() {
      return this.suggestedFilteredTags.filter((el) => el.isNew !== true).length <= 1;
    }

  },

  created() {
    if (this.minInput <= 0) {
      // Init an empty search

      this.onSearch('', function () {
      });
    }
  },


  methods: {
    onInputChanged(currentValues) {

      const newObjects     = _differenceBy(currentValues, this.validTypesValues, (el) => el.id);
      const removedObjects = _differenceBy(this.validTypesValues, currentValues, (el) => el.id);

      // console.log(newObjects, removedObjects);

      for (let i in newObjects) {
        this.$emit('input:added', newObjects[i]);
      }

      for (let i in removedObjects) {
        this.$emit('input:removed', removedObjects[i]);
      }

      this.$emit('input', this.invalidTypesValues.concat(currentValues));

    },


    getTagLabelFromObject(value) {
      if (typeof value === 'object') {
        if (!value.hasOwnProperty('title')) {
          return console.warn(
              `[vue-select warn]: Label key "option.title" does not` +
              ` exist in options object ${JSON.stringify(value)}.\n` +
              'http://sagalbot.github.io/vue-select/#ex-labels'
          )
        } else {
          return value.title;
        }

      } else {
        return value;
      }
    },

    displayBibleverse(from, to) {
      this.biblePopover.bibleverse = new BibleVerse(from, to);
      this.biblePopover.showMe     = true;
    },

    copyTagContent(label) {
      copyStringToClipboard(label);
    },

    onSearch(search, loading) {
      loading(true);

      this.suggestedFilteredTags = [];

      // _debounce(this.default.methods.search2(search, loading).bind(this), 250);
      console.log('onSearch', search);

      _debounce(function () {
        console.log('debounced');
        // this.search(loading, search);
      }, 250);

      // this.search(loading, search);
    },

    // _.debounce is a function provided by lodash to limit how
    // often a particularly expensive operation can be run.
    // To learn
    // more about the _.debounce function (and its cousin
    // _.throttle), visit: https://lodash.com/docs#debounce
    // search: _debounce((loading, search, vm) => {
    search(loading, search) {

      if (search.length < this.minInput) {
        this.suggestedFilteredTags = [];
        loading(false);
        return;
      }

      const vm = this;

      this.$store.dispatch('keywords/search', {
        searchText: search,
        type: this.typefilter || false,
        limit: 2
      })
          .then(({keywords, pagination}) => {
            // console.log(keywords);
            this.suggestedFilteredTags = keywords.filter(this.filterSuggestionsBy);

            if (this.newTagsEnabled === true) {
              const newTag = {
                title: search,
                isNew: true,
                id: 'new Keyword: ' + search,
              };

              if (this.typefilter) {
                newTag.type = this.typefilter;
              }

              this.hasMoreResults = pagination.hasMore;
              this.page           = pagination.current_page;

              this.suggestedFilteredTags.push(newTag);
            }

          })
          .catch(function (err) {
            console.error(err);
          })
          .then(function () {
            // Always
            loading(false);
          });

    },

    filterSuggestionsBy(object) {
      return this.value.find((el) => el.id === object.id) === undefined;
    },

    openRightClickMenu(event, keywordForEvent) {
      this.$refs.menu.openMenu(event, keywordForEvent)
    },

    updateKeywordChanges(keyword) {
      const valEl = this.value.find((el) => el.id = keyword.id);

      if (valEl) {
        for (let i in keyword) {
          valEl[i] = keyword[i];
        }
      }

      // this.$emit('input:data-changed', keyword);
    },

    doToTagSearch(keyword) {
      this.$router.push({
        name: 'search',
        params: {
          search: searchArrayObjectsToSearchQuery([[keyword]])
        }
      });
    },


  },

  components: {
    BiblePopover,
    KeywordEditor,
    ContextMenuItem,
    ContextMenu,
    DragableElement,
    tagIcon,
    VueSelect
  }
}
</script>

<style lang="scss">
@import "resources/sass/theme";

.tagEditSidebarField {
  .editField {

    .vs__dropdown-toggle {
      background-color: $sidebar-input-background-colour-active;

      .vs__selected-options {

        margin-top: 3px;

        input {
          min-width: 50%;

          &::placeholder {
            color: $sidebar-input-text-colour-placeholder;
          }
        }

        button.vs__deselect {
          color: $sidebar-input-font-color-active-hover;
        }
      }
    }

    .hasElements {
      .vs__selected-options {
        padding: $sidebar-input-padding-top $sidebar-input-padding-right $sidebar-input-padding-bottom $sidebar-input-padding-left;
      }
    }

    .vs--disabled {
      .selected-tag {
        background-color: $sidebar-tag-background-color-disabled;
        color: $sidebar-input-font-color-disabled;
      }

      .selected-relevance {
        background-color: $sidebar-tag-relevance-colour-disabled;
      }

      .vs__search {
        display: none;
      }

      .vs__actions {
        display: none;
      }

      .vs__dropdown-toggle {
        // background-color: $vs-state-disabled-bg;
        cursor: not-allowed;
      }

    }

    .vs__dropdown-option {

      padding-left: .5em;

      .tagOptionIcon svg {
        width: 1em;
        height: 1em;
      }
    }
  }

  .suggested-option.is-new .suggested-text {
    text-decoration: underline;
    padding-right: .5em;
  }
}

</style>