<template>
  <div class="container">

    <div id="ReadBibleInputGroup" class="pb-3">
      <b-input-group>
        <b-form-input
            v-model="searchInput"
            :placeholder="$t('pool.Insert-bibleverse-here')"
            @keydown.enter="analyseSearchInput"/>

        <template #append>
          <b-dropdown :text="dropDownLabel" variant="secondary"
                      :title="$t('pool.Select-Translation')">
            <b-dropdown-item v-for="bible in allBibles" :key="bible.uuid" @click="bibleUuid=bible.uuid">
              {{ bible.title }}
            </b-dropdown-item>
          </b-dropdown>
        </template>
      </b-input-group>
    </div>

    <div class="range pt-3 materialpool-jumbotron">
      <bible-text
          v-for="(bv, rangeIndex) in bibleVerses"
          :key="'bvr' + rangeIndex"
          :bible-uuid="bibleUuid"
          :bibleverse="bv"
      ></bible-text>
    </div>


  </div>
</template>

<script>
import {searchArrayObjectsToSearchArrayItems}                               from "../../../components/search/searchHelper";
import {BDropdown, BDropdownItem, BFormInput, BInputGroup} from "@/adapters/bootstrap";
import BibleText
                                                                            from "../../../components/biblecontents/bibleText";
import {BibleVerse, BibleVerseService}                                      from "../../../helper/BibleverseHelper";
import {fromRangeArrayToString}                                             from './readBibleHelper';
import {useBiblesStore}                                                     from '../stores/bibles';
import {useBibleContentsStore}                                              from '../stores/bibleContents';
import {useBibleversesStore}                                                from '../stores/bibleverses';
import {useSearchStore}                                                     from '../stores/search';

export default {
  name: "ReadBible",

  data() {
    return {
      bibleUuid: null,

      bibleVerses: [],
      searchInput: '',
      bibleVerseContent: [],

    };
  },

  asyncComputed: {
    allBibles: {
      get() {
        return useBiblesStore().getAll().then((bibles) => {
          return bibles;
        });
      },
      default: []
    },

    ranges: {
      get() {
        return useBibleContentsStore().getMultiple(this.bibleVerses.map(bv => {
              return {
                from: bv.getFrom(),
                to: bv.getTo(),
                bibleUuid: this.bibleUuid
              };
            })
        );
      },
      default() {
        return [];
      },
      deep: true,
      watch() {
        this.bibleVerses
      }
    },

    materials: {
      get() {
        if (this.bibleVerses.length === 0) {
          return [];
        }

        const searchData = searchArrayObjectsToSearchArrayItems([this.bibleVerses]);

        return useSearchStore().materials({query: searchData});
      },
      default: null,
      watch() {
        this.bibleVerses
      }
    }
  },

  computed: {
    selectableBibleOptions() {
      return this.allBibles.map((b) => {
        return {
          value: b.uuid,
          text: b.title
        };
      })
    },

    dropDownLabel() {
      if (this.bibleUuid) {
        return this.allBibles.find(b => {
          return b.uuid === this.bibleUuid;
        }).title;
      } else {
        return this.$t('pool.Translation');
      }
    }
  },

  methods: {
    initVerses(verses) {

      this.bibleVerses = verses.map((bv) => {
        return new BibleVerse(bv.from, bv.to);
      });

      this.searchInput = this.bibleVerses.map(bv => BibleVerseService.bibleVerseToString(bv)).join(', ')

    },

    analyseSearchInput() {

      useBibleversesStore().search(this.searchInput)
          .then(bibleverses => {
            this.updateRoute(bibleverses)
          });

    },

    updateRoute(verses) {

      this.$router.push({
        name: 'readbible',
        params: {
          searchquery: fromRangeArrayToString(verses)
        }
      });

    },

    fromStringToRangeArray(text) {

      const query       = text || '';
      const verseranges = query.split(',');

      return verseranges
          .filter(text => text !== '')
          .map((range) => {
            const split = range.split('-');

            return {
              from: parseInt(split[0]),
              to: parseInt(split[1]),
              bibleId: split.length > 2 ? parseInt(split[2]) : null
            };
          });
    },

    toCaption(bibleverse, displayLength) {
      return BibleVerseService.bibleVerseToString(bibleverse, displayLength);
    }
  },

  beforeRouteEnter(to, from, next) {
    next(vm => {
      vm.initVerses(vm.fromStringToRangeArray(to.params.searchquery || ''));
    });
  },

  beforeRouteUpdate(to, from, next) {
    this.initVerses(this.fromStringToRangeArray(to.params.searchquery || ''));
    next();
  },

  components: {
    BibleText,
    BInputGroup,
    BFormInput,
    BDropdown,
    BDropdownItem,
  },

}
</script>

<style scoped>

</style>
