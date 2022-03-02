<template>
  <b-modal
      scrollable
      size="lg"
      ref="myModal"
      @hide="_cancelPromise"
      dialog-class="sb-optimize-keywords"
      header-class="d-block"
  >

    <template #modal-header="{ close }" class="d-block">
      <!-- Emulate built in modal header close button action -->

      <div class="d-flex align-items-start justify-content-between mb-2">
        <div class="keyword-listing mr-2">
          <search-input-tag v-for="(kw,index) in currentKeywordSelection"
                            v-bind="kw"
                            :class="{'is-selected' : kw === selectedTag}"
                            :show-cross-refs="true"
                            :is-selectable="_itemIsSelectable(kw)"
                            :disabled="!_itemIsSelectable(kw)"
                            @deselect="_removeKeywordFromSelection(index)"
                            @select="_changeSelection(kw)"/>
        </div>

        <div class="ml-2">
          <h5>{{ $t('pool.Optimize-Keywords') }}</h5>
          <b-form-input type="range"
                        debounce="500"
                        v-model="maxDisplayedSuggestions"
                        :min="minDisplayedSuggestions" :max="tagSuggestionsCount" step="1"
                        :disabled="tagSuggestionsCount <= 0"></b-form-input>
        </div>
      </div>

      <div class="d-block selection small" v-if="selectedTagDisplay.length > 0">
        "{{ selectedTagDisplay | trim(300) }}"
      </div>


    </template>


    <div class="main">
      <b-list-group class="suggestions">
        <b-list-group-item v-for="sug in tagSuggestionsFiltered" class="flex-column align-items-start">
          <div class="d-flex w-100 justify-content-between">
            <!--<h5 class="mb-1">{{ sug.headline }}</h5>-->
            <search-input-tag v-bind="sug.searchItem" :show-disable="false" :is-selectable="true"
                              @select="_addKeywordToSelection(sug.searchItem)"/>
            <small>{{ $t('pool.relevance') }}: {{ sug.relevance }}</small>
          </div>

          <p class="mb-1 mt-2" v-if="sug.bigText && sug.bigText.length && sug.bigText.length > 0">{{ sug.bigText }}</p>

          <small v-if="sug.smallText && sug.smallText.length && sug.smallText.length > 0">{{ sug.smallText }}</small>
        </b-list-group-item>
      </b-list-group>
    </div>


    <template #modal-footer>
      <button type="button" class="btn btn-danger btn-sm" @click="hide">{{ $t('pool.Cancel') }}</button>
      <button type="button" class="btn btn-primary btn-sm" @click="returnSelection">{{ $t('pool.Take-it') }}</button>
    </template>


  </b-modal>
</template>

<script>

import {BButton, BFormInput, BListGroup, BListGroupItem, BModal} from 'bootstrap-vue';
import MaterialpoolSpinner                                       from "../../spinner/materialpool-spinner";
import SearchInputTag                                            from "../../search/searchInputTag";
import {objectToSearchItem}                                      from "../../search/searchHelper";
import {BibleVerse, BibleVerseService}                           from "../../../helper/BibleverseHelper";
import truncateFilterMixin                                       from "../../../filters/truncate-filter.mixin";

export default {
  name: "optimizeKeywordSearch",
  mixins: [truncateFilterMixin],

  props: {},

  data() {
    return {
      reject: null,
      resolve: null,

      selectedTag: null,
      currentKeywordSelection: [],

      maxDisplayedSuggestions: 1,
      minDisplayedSuggestions: 1,

      searchOngoing: false,
    };
  },

  computed: {
    selectedBibleveres() {
      return this.currentKeywordSelection.filter(el => el?.item?.type === 'b');
    },

    // Filtere alle searchItems heraus, die bereits in der Liste enthalten sind
    tagSuggestionsFiltered() {

      const result = this.tagSuggestions.filter((el) => {

        const b1 = el?.searchItem;
        if (b1?.item?.type === 'b') {
          return !this.selectedBibleveres.some(b2 => {
            return b2?.item?.from === b1?.item?.from && b2?.item?.to === b1?.item?.to;
          });
        }

        return true;
      });


      // Bereits angezeigte Bibelverse müssen abgezogen werden, da die ja nicht angezeigt werden
      this.minDisplayedSuggestions = Math.max(1, this.tagSuggestions.length - result.length + 1);

      return result;

    }

  },

  asyncComputed: {

    selectedTagDisplay: {
      async get() {
        let result = '';
        switch (this.selectedTag?.item?.type) {
          case 'b':
            const verses = await this.$store.dispatch('biblecontents/get',
                {from: this.selectedTag?.item?.from, to: this.selectedTag?.item?.to}
            );

            result = verses.map(b => b.text).join(' ');
        }

        return result;

      },
      default: ''
    },

    tagSuggestionsCount: {
      async get() {
        let count = 1;
        switch (this.selectedTag?.item?.type) {
          case 'b':
            count = await this.$store.dispatch('bibleversecrossreferences/getCount',
                {from: this.selectedTag?.item?.from, to: this.selectedTag?.item?.to}
            );
        }

        // Aktualisiere die tatsächliche Auswahl abhängig von dem, was wirklich geht
        this.maxDisplayedSuggestions = Math.min(this.maxDisplayedSuggestions, count);

        return count;
      },

      default: 1
    },


    tagSuggestions: {

      async get() {
        // Returns:
        /*
        [{
        type: 'b',
        relevance: INT,
        searchItem: {}
        }]
         */

        switch (this.selectedTag?.item?.type) {
          case 'b':

            const crossRefs = await this.$store.dispatch('bibleversecrossreferences/get',
                {
                  from: this.selectedTag?.item?.from,
                  to: this.selectedTag?.item?.to,
                  maximum: this.maxDisplayedSuggestions
                }
            );

            // Parse crossRefs to Bibleverses
            let bibleverseCollection = []; // Collection of bibleverses to load the bibletext

            return crossRefs.map((crossRef) => {

              const from       = crossRef.target_from;
              const to         = crossRef.target_to !== 0 ? crossRef.target_to : crossRef.target_from;
              const bibleverse = new BibleVerse(from, to);

              bibleverseCollection.push(bibleverse);

              const result = {
                searchItem: objectToSearchItem(bibleverse),
                relevance: crossRef.relevance,
                headline: BibleVerseService.bibleVerseToString(bibleverse, 'long'),
                bigText: '',
                smallText: 'is loading ...',

                extra: {bibleverse}
              }

              this.$store.dispatch('biblecontents/get', {from, to})
                  .then((resp) => {
                    result.bigText = '';

                    if (resp.length > 0) {
                      this.$store.dispatch('bibles/get', resp[0].bibleUuid)
                          .then(({title}) => {
                            result.smallText = title;
                          });
                    }

                    result.bigText = '"' + resp.map(({text}) => text).join(' ') + '"';

                  });

              return result;

            });

          case 'k':
            return [];
          default:
            return [];
        }

      },
      default() {
        return [];
      }
    }

  },

  methods: {

    _removeKeywordFromSelection(index) {
      const removed = this.currentKeywordSelection.splice(index, 1);
    },

    _addKeywordToSelection(searchItem) {
      this.currentKeywordSelection.push(searchItem);
    },

    _changeSelection(kw) {
      if (this.selectedTag !== kw) {
        this.selectedTag = kw;

        this.$nextTick(() => {
          this.maxDisplayedSuggestions = this.minDisplayedSuggestions + 5;
        })

      }
    },

    _itemIsSelectable(item) {
      const type = item?.item?.type;
      return type === 'b'; // || type === 'k'
    },

    _initKeywordSelection(initKeywords) {
      this.currentKeywordSelection = JSON.parse(JSON.stringify(initKeywords));
    },

    showPromise(startKeywords) {

      this._initKeywordSelection(startKeywords);
      return new Promise((resolve, reject) => {
        this.resolve = resolve;
        this.reject  = reject;

        this.$refs.myModal.show();

      });

    },

    _cancelPromise() {

      if (typeof this.reject === 'function') {
        this.reject('closed early');
        // this.$refs.myModal.close();
        this.resolve = null;
        this.reject  = null;
      }

    },


    returnSelection() {
      if (typeof this.resolve === 'function') {
        this.resolve(this.currentKeywordSelection);
        this.$refs.myModal.hide();
        // this.resolve = null; // already done during hide()
        // this.reject  = null; // already done during hide()
      }
    },

    hide() {
      this.$refs.myModal.hide();
    },


  },

  components: {
    SearchInputTag,
    MaterialpoolSpinner,
    BModal,
    BButton,
    BListGroup, BListGroupItem,
    BFormInput
  }


}
</script>

<style scoped lang="scss">
@import "../../../../sass/theme";

.sb-optimize-keywords {

  .keyword-listing {
    appearance: none;
    display: flex;
    flex-wrap: wrap;
    padding: 0 0 4px 0;
    background: $vs-component-bg;
    border: $vs-border-width $vs-border-style $vs-border-color;
    border-radius: $border-radius;
    white-space: normal;

    .is-selected {
      box-shadow: 0 0 10px 1px #0ff;
      border-color: #0ff;
    }
  }
}
</style>