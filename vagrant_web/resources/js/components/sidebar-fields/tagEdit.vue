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


      <div class="buttons" v-if="!disabled">
        <slot name="buttons" v-bind="{options:validTypesValues}">
          <b-button
              v-if="batchEditRelevanceEnabled && validTypesValues.length > 1"
              size="sm"
              class="multi-edit-button"
              variant="outline-secondary"
              @click="openRelevanceSelector">
            {{ $t('pool.batch-edit-relevance') }}
          </b-button>
        </slot>
      </div>
    </div>

    <div class="editField">

      <slot name="input">
        <vue-select
            :class="{hasElements : hasOneValueOrMore }"
            :clearSearchOnSelect="_clearSearchOnSelect"
            :close-on-select="_closeOnSelect"
            :disabled="disabled"
            :filterable="false"
            :getOptionLabel="_getTagLabelFromObject"
            :getOptionKey="_getOptionKey"
            :multiple="multipleTags"
            :options="optionsWithNewTag"
            :placeholder="placeholder"
            :selectOnTab="true"
            :model-value="validTypesValues"
            @open="onOpen"
            @close="onClose"
            @update:model-value="onInputChanged"
            @search="onSearchTermChanged"
            ref="dropdown"
        >

          <template v-if="multipleTags" v-slot:selected-option-container="{option, disabled, deselect}">
            <dragable-element
                :id="option.id"
                :disable-move-relevance="disabled"
                :disable-remove-element="disabled"
                :label="_getTagLabelFromObject(option)"
                :relevance="option.pivot.relevance"
                @deselect="deselect(option)"
                @single-click="$emit('request-info', option)"
                @request-update-relevance="$emit('request-update-relevance', {tag: option, relevance: $event})"
                @click:right="openRightClickMenu($event, option)"/>
          </template>

          <template v-slot:option="option">
            <span :class="{'is-new' : option.isNew}"
                  class="suggested-option">
              <span class="tagOptionIcon">
                <slot name="icon">
                    <tag-icon/>
                </slot>
              </span>
              <span class="suggested-text">
                {{ _getTagLabelFromObject(option) }}
              </span>
              <span v-if="option.isNew" class="is-new badge text-bg-info">{{ $t('pool.new') }}</span>
            </span>
          </template>

          <template v-slot:no-options>
            <template v-if="!isSearchTermValid">
              {{
                $tc('pool.insert-more-character', minInput - searchTerm.length, {character: minInput - searchTerm.length})
              }}
            </template>
            <template v-else>
              {{ $t('pool.no-results') }}
            </template>
          </template>

          <template v-slot:list-footer="{filteredOptions}">
            <li v-show="hasMoreResults && isSearchTermValid" ref="load" class="loader">
              {{ $t('pool.loading-more-results') }}
            </li>
            <li v-show="!hasMoreResults && isSearchTermValid && filteredOptions.length > 0"
                class="loader">
              {{ $t('pool.no-more-results') }}

              <template
                  v-if="batchNewTagsEnabled && filteredOptions.filter(_o => !$refs.dropdown.isOptionSelected(_o)).length > 1">
                <button
                    class="btn btn-sm btn-warning add-all-tags"
                    @click.prevent.stop="()=> {
                    filteredOptions
                    .filter(_o => !$refs.dropdown.isOptionSelected(_o))
                    .forEach(_o => $refs.dropdown.select(_o));
                  }">
                  {{
                    $tc('pool.add-all-xy-tags',
                        filteredOptions.filter(_o => !$refs.dropdown.isOptionSelected(_o)).length,
                        {xy: filteredOptions.filter(_o => !$refs.dropdown.isOptionSelected(_o)).length}
                    )
                  }}
                </button>
              </template>

            </li>
          </template>

        </vue-select>
      </slot>

    </div>

    <context-menu ref="menu" v-slot:default="{optionalData}">
      <context-menu-item v-if="!disabled && !optionalData.from"
                         @click.stop="$refs.keywordEditor.show(optionalData.id)">
        {{ $t('pool.edit') }}
      </context-menu-item>

      <context-menu-item :to="getToTagSearch(optionalData)">
        {{ $t('pool.search-for-xy', {xy: _getTagLabelFromObject(optionalData)}) }}
      </context-menu-item>
      <context-menu-item v-if="optionalData.from && optionalData.to"
                         @click="displayBibleverse(optionalData.from, optionalData.to)">
        {{ $t('pool.Read-Bibleverse') }}
      </context-menu-item>

      <context-menu-item @click="copyTagContent(_getTagLabelFromObject(optionalData))">
        {{ $t('pool.Copy') }}
      </context-menu-item>
    </context-menu>

    <keyword-editor
        :id="0"
        ref="keywordEditor"
        @saved="updateKeywordChanges"/>

    <bible-popover v-if="biblePopover.showMe" :bibleverse="biblePopover.bibleverse" :position="$refs.sideBarField"
                   @bible-popover-closerequest="biblePopover.showMe = false"/>

    <relevance-selector
        ref="relevanceSelector"
        :num-tags="value.length"
        v-if="!disabled && batchEditRelevanceEnabled"
    ></relevance-selector>

    <!--
     @deleted="onDeleted"/>
    @saved="onKeywordPropertiesChanged" -->

  </div>
</template>

<script>
import generalMixin                                                      from './generalSidebarFields.mixin';
import VueSelect                                                         from '@/adapters/vue-select';
import tagIcon                                                           from '@icons/vendor/svg-icon/svg/material/style.svg';
import {keywordTypes}                                                    from "../keyword/keywordDefaultIcons";
import cloneDeep                                                         from 'lodash/cloneDeep';
import _debounce                                                         from 'lodash/debounce';
import _differenceBy                                                     from 'lodash/differenceBy';
import DragableElement                                                   from "./vue-select/dragable-element";
import ContextMenu                                                       from "../context-menu/context-menu";
import ContextMenuItem                                                   from "../context-menu/context-menu-item";
import {searchArrayObjectsToSearchQuery}                                 from "../search/searchHelper";
import {defineAsyncComponent}                                            from 'vue';

import BiblePopover                                                  from "../bible-popover/bible-popover";
import BibleVerse
                                                                     from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/in/BibleVerse.js';
import {copyStringToClipboard}                                       from "../../helper/copyToClipboard";
import {getOptionKeyFromKeywordObject, getTagLabelFromKeywordObject} from "./tagEdit_functions";
import {savingDialogs}                                               from "../../helper/flashMessages";
import RelevanceSelector                                             from "../modals/dialogs/relevanceSeletor.vue";
import {BButton}                                                     from "@/adapters/bootstrap";
import {useKeywordsStore}                                            from '../../apps/main/stores/keywords';


export default {
  name: "tagEdit",

  mixins: [generalMixin, savingDialogs],

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
      default: 2
    },

    newTagsEnabled: {
      type: Boolean,
      required: false,
      default: true
    },

    batchNewTagsEnabled: {
      type: Boolean,
      required: false,
      default: false
    },

    batchEditRelevanceEnabled: {
      type: Boolean,
      required: false,
      default: false
    }

  },

  data() {
    return {
      suggestedTags: [],
      clearOnSelect: true,

      // Pagination
      observer: null, // Überwacht den Footer der Dropdown-Liste. Bei Sichtbarkeit werden ggf. weitere Elemente nachgeladen
      searchTerm: '', // Entspricht der aktuellen Eingabe im Suchfeld (wird per event aktualisiert)
      hasMoreResults: true,
      page: 1,

      // Bei jeder Suche wird der Counter um eins erhöht und im queryHandler als Objekt sowohl die Suche, als auch das Ergebnis protokolliert
      // Nach erfolgreichem Abschluss und Handling der Suche, wir das Objekt im queryHandler wieder aufgheräumt
      queryCounter: 0,
      queryHandler: {},

      biblePopover: {
        showMe: false,
        bibleverse: null
      },

      multipleTags: true,

    };
  },

  computed: {
    validTypesValues() {
      // About bind: https://stackoverflow.com/questions/49714015/why-does-this-inside-filter-gets-undefined-in-vuejs
      if (this.multipleTags) {
        return this.value.filter(function (el) {
              return el.type === this.typefilter || this.typefilter === '';
            }.bind(this)
        );
      } else {
        return this.value;
      }
    },

    hasOneValueOrMore() {
      if (this.multipleTags) {
        return this.validTypesValues.length > 0;
      } else {
        return this.validTypesValues !== null;
      }
    },

    invalidTypesValues() {
      if (this.multipleTags) {
        return this.value.filter(function (el) {
              return el.type !== this.typefilter && this.typefilter !== '';
            }.bind(this)
        );
      } else {
        return false;
      }
    },

    _clearSearchOnSelect() {
      // Texteingabe löschen, wenn nur noch eines sichtbar war, oder wenn es eine Single-Seletct-Eingabe ist
      return !this.multipleTags || this.suggestedTags.filter((opt) => {
        return !this.$refs.dropdown.isOptionSelected(opt);
      }).length <= 1;
    },

    _closeOnSelect() {
      return this._clearSearchOnSelect;
    },

    isSearchTermValid() {
      // console.log('SearchTerm validation updated', this.searchTerm.length >= this.minInput);
      return String(this.searchTerm).length >= this.minInput;
    },

    optionsWithNewTag() {

      // Wenn der Suchstring zu kurz, gib ein leeres Ergebnis zurück
      if (false === this.isSearchTermValid) {
        return [];
      }

      const options = [...this.suggestedTags];

      if (this.newTagsEnabled === true && this.searchTerm.length >= this.minInput) {

        // Prüfe, ob der Suchtext so schon vorkommt in einem der Values
        if (this.suggestedTags.find((el) => el.title === this.searchTerm) === undefined) {

          const newTag = {
            title: this.searchTerm,
            type: this.typefilter,
            isNew: true,
            id: 'new Keyword: ' + this.searchTerm,
          };

          if (this.typefilter) {
            newTag.type = this.typefilter;
          }

          options.push(newTag);
        }

      }

      return options;

    }

  },

  created() {
    if (this.minInput <= 0) {
      // Init an empty search
      if (this.isSearchTermValid) {
        const {queryCache, counter} = this.doSearch(this.searchTerm, 1);
        this.handleQueryResult(queryCache, counter);
      }

    }
  },

  mounted() {
    // See https://vue-select.org/guide/infinite-scroll.html
    this.observer = new IntersectionObserver(this.onInfiniteScrollReaced)
  },


  methods: {
    onInputChanged(currentValues) {

      const newObjects     = _differenceBy(currentValues, this.validTypesValues, (el) => el.id);
      const removedObjects = _differenceBy(this.validTypesValues, currentValues, (el) => el.id);

      // console.log(newObjects, removedObjects);

      if (removedObjects.length > 0 || newObjects.find((el) => el.isNew) !== undefined) {
        // Reset SearchResults

        // Because: Removed Keywords might have been lonely and deleted at the server
        // Therefore we MIGHT not be able to assign them anymore ... but have to create them first again

        // Gleiches auch, wenn ein neues keyword erstellt werden musste ...
        // Das kann in der Liste nur dann als hinzugefügt erkannt werden, wenn die ID dabei ist.
        // Und die Id erfahren wir erst nach dem nächsten Ladevorgang
        this.page           = 1;
        this.hasMoreResults = true;
        this.suggestedTags  = [];
        this.onSearchTermChanged(this.searchTerm, () => true);
      }

      for (let i in newObjects) {
        this.$emit('input:added', (newObjects[i]));
      }

      for (let i in removedObjects) {
        this.$emit('input:removed', (removedObjects[i]));
      }

      const allValues = this.invalidTypesValues.concat(currentValues);
      this.$emit('input', allValues);

    },

    displayBibleverse(from, to) {
      this.biblePopover.bibleverse = new BibleVerse(from, to);
      this.biblePopover.showMe     = true;
    },

    copyTagContent(label) {
      copyStringToClipboard(label);
    },

    async onOpen() {
      // Initialisiere den Observer, sobald es im DOM ist (nach dem nextTick)
      // Der Observer wird ausgelöst, sobald das Element this.$refs.load (im Footer) sichtbar wird
      // Dann werden weitere Ergebnisse nachgeladen
      await this.$nextTick();
      this.observer.observe(this.$refs.load)
    },

    onClose() {
      this.observer.disconnect();
    },

    async onInfiniteScrollReaced([{isIntersecting, target}]) {

      if (isIntersecting) {

        if (!this.isSearchTermValid || !this.hasMoreResults) {
          return;
        }

        // console.log('is INTERSECTING', this, this.searchTerm, this.page);

        if (this.queryHandler.hasOwnProperty(this.queryCounter)) {
          if (this.queryHandler[this.queryCounter].isLoading === true) {
            // console.log('Cancel Infinite load before starting it - page one has not loaded yet');
            return;
          }
        }

        // do search next page
        const {queryCache, counter} = this.doSearch(this.searchTerm, ++this.page);

        const resultsApplied = await this.handleQueryResult(queryCache, counter);

        if (true === resultsApplied) {

          const ul        = target?.offsetParent;
          const scrollTop = target?.offsetParent?.scrollTop;

          if (ul && scrollTop) {
            await this.$nextTick();
            ul.scrollTop = scrollTop;
          }

        }

      } else {
        // console.log('is NOT intersecting', isIntersecting, target);
      }

    },

    onSearchTermChanged: _debounce(function (query, loadingCallback) {

      // Immer der aktuelle Such-Wert (ohne Debouncing
      this.searchTerm = query;

      // console.log('searchTerm Updated: ', query, this);

      // do first search (=> page = 1)
      const {queryCache, counter} = this.doSearch(query, 1);

      this.handleQueryResult(queryCache, counter)
          .then(() => {
            loadingCallback(false);
          });

    }, 250),

    doSearch(query, page) {

      const counter    = ++this.queryCounter;
      const queryCache = {
        query,
        page,
        isLoading: true
      }

      this.queryHandler[counter] = queryCache;

      // console.log('Loading No' + counter + '...: "' + query + '"', 'Page ' + page);

      queryCache.promise = useKeywordsStore()
                               .search({
                                 searchText: query,
                                 type: this.typefilter || false,
                                 limit: 20,
                                 page: page,
                               })
                               .then(({keywords, pagination}) => {

                                 queryCache.isLoading = false;

                                 // console.log('Loaded No' + counter + '...: ' + query, 'Page ' + page, queryCache);

                                 return {
                                   keywords,
                                   hasMore: pagination.hasMore,
                                   current_page: pagination.current_page
                                 };

                               })
                               .catch(function (err) {
                                 console.error(err);
                               });

      return {queryCache, counter};

    },


    /**
     *
     * @param queryCache
     * @param counter
     * @returns {Promise<boolean>} True if the result was applied | false if the result was too old already
     */
    async handleQueryResult(queryCache, counter) {

      // QueryCache includes: query, page, isLoading, promise

      const {keywords, hasMore, current_page} = await queryCache.promise;

      // Das Ergebnis ist schon nicht mehr die neuste Suche => wirf alles über den Haufen
      if (counter !== this.queryCounter) {
        delete this.queryHandler[counter];
        return false;
      }

      // Das Ergebnis passt schon nicht mehr zur aktuellen Suchabfrage => behalte es trotzudem noch, weil noch keine anderen Debounced Werte da sind
      /*
      if (queryCache.query !== this.searchTerm) {
        console.log('Suchergebnis passt schon nicht mehr zum aktuellen Suchbegriff - es wird trotzdem angezeigt. Macht das Sinn?');
      }
       */

      // Deep Copy
      const tags = cloneDeep(keywords); // JSON.parse(JSON.stringify(keywords));

      // Set or append tags
      if (current_page > 1) {
        this.suggestedTags.push(...tags);
      } else {
        this.suggestedTags = tags;
      }

      // Update Page
      this.page = current_page;

      // Update hasMore
      this.hasMoreResults = hasMore;

      // Cleanup
      delete this.queryHandler[counter];

      return true;

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

    getToTagSearch(keyword) {
      return {
        name: 'search',
        params: {
          search: searchArrayObjectsToSearchQuery([[keyword]])
        }
      };
    },

    openRelevanceSelector() {

      this.$refs.relevanceSelector.show()
          .then(({relevance}) => {

            this.value.forEach((_op) => {
              this.$emit('request-update-relevance', {tag: _op, relevance});
            });

          })
          .catch(() => {
          });
    },

    _getTagLabelFromObject: getTagLabelFromKeywordObject,
    _getOptionKey: getOptionKeyFromKeywordObject,


  },

  components: {
    RelevanceSelector,
    BiblePopover,
    BButton,
    KeywordEditor: defineAsyncComponent(() => import('../modals/editors/keywordEditor')),
    ContextMenuItem,
    ContextMenu,
    DragableElement,
    tagIcon,
    VueSelect
  }
}
</script>

<style lang="scss">
@use "resources/sass/theme" as *;

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

      &.vs__dropdown-option--selected {
        color: $sidebar-input-text-colour-placeholder;
        cursor: default;

        svg path {
          fill: $sidebar-input-text-colour-placeholder;
        }

        &.vs__dropdown-option--highlight {
          background-color: $sidebar-input-background-colour-disabled;
        }
      }

      &.vs__dropdown-option--highlight {
        background-color: $primary;

        svg path {
          fill: $body-bg;
        }
      }
    }

    .loader {
      text-align: center;
      color: $sidebar-input-text-colour-placeholder;

      .add-all-tags {
        font-size: .9em;
      }
    }

  }

  .suggested-option.is-new .suggested-text {
    text-decoration: underline;
    padding-right: .5em;
  }

}

</style>
