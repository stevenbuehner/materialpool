<template>
  <div class="card">

    <!-- HEADLINE AND GENERAL BUTTONS-->
    <div class="card-header d-flex flex-row justify-content-between">
            <span class="mt-auto">
                {{ $t('pool.Keywords') }}
            </span>

      <button class="btn btn-sm btn-danger"
              :title="$t('pool.remove-all')"
              v-if="myKeywords.length > 0"
              type="button"
              @click="btnRemoveAllKeywords">
        <erase-svg class="icon-erase"/>
      </button>
    </div>


    <!-- ALREADY EXISTING KEYWORDS-->
    <div class="card-body">
      <keyword v-for="(kw, index) in myKeywords"
               :key="getKeywordkey(kw)"
               v-model="myKeywords[index]"
               :material-id="materialId"
               :removeable="!disabled"
               :editable="!disabled"
               :searchable="!disabled"
               :dragable="!disabled"
               @removed="keywordRemoved"
               @saved="keywordUpdated(kw, index); flashSaved('Keyword')"
               @saving="flashStartSaving('Keyword')"
               @savingPivot="flashStartSaving('Keyword Piot')"
               @savingError="flashUpdateTagError"
               @savingPivotError="flashUpdateTagError"
      ></keyword>
    </div>


    <!-- SEARCH INPUT -->
    <div class="card-body" v-if="!disabled">

      <div class="input-group">
        <input class="form-control"
               type="text"
               ref="myInput"
               :placeholder="$t('pool.Insert-keywordtext-here')"
               v-model="keywordInput"
               autocorrect="off"
               @keyup.enter="requestCreateNewKeyword">
        <div class="input-group-append">
          <button class="btn btn-outline-secondary"
                  type="button"
                  :class="{'disabled' : keywordInput.length < 3}"
                  v-if="!stillLoading"
                  @click="requestCreateNewKeyword"
          >{{ $t('pool.new') }}
          </button>

          <button v-if="stillLoading"
                  class="btn btn-outline-secondary spinnerBlock"
                  type="button">
            <materialpool-spinner/>
          </button>
        </div>
      </div>

    </div>


    <!-- SEARCH SUGGESTIONS -->
    <div class="card-footer" v-if="(displayableSuggestedKeywords.length > 0 || stillLoading) && !disabled">
      <span v-if="stillLoading">{{ $t('pool.Looking-for-suggestions') }}</span>

      <h4 v-if="!stillLoading && suggestedKeywords.length > 0">
        {{ $t('pool.Suggestions') }}:</h4>
      <transition-group name="fade">
        <button type="button"
                class="btn btn-outline-secondary btn-sm me-1 mb-1"
                v-if="!stillLoading"
                v-for="kw in displayableSuggestedKeywords"
                :key="'s' + kw.id"
                @click="requestAddKeyword(kw)"
        >{{ kw.title }}
        </button>
      </transition-group>
    </div>

  </div>
</template>

<script>
import Keyword              from './keyword.vue';
import _debounce            from 'lodash/debounce';
import eraseSvg             from 'svg-icon/dist/svg/zero/clear.svg';
import {RELEVANCE_USER_MAX} from "../../apps/config";
import MaterialpoolSpinner  from "../spinner/materialpool-spinner";
import {savingDialogs}      from "../../helper/flashMessages";
import {cloneDeep}          from "lodash";


export default {

  mixins: [savingDialogs],

  props: {
    // Only passing in. Later working with myKeywords
    keywords: {
      type: Array,
      required: false,
      default() {
        return [];
      }
    },

    materialId: {
      type: Number,
      required: false
    },

    disabled: {
      type: Boolean,
      required: false,
      default: false
    },

  },

  model: {
    prop: 'keywords',
    event: 'updated'
  },

  data() {
    return {
      myKeywords: [],

      keywordInput: '',

      stillLoading: false,
      suggestedKeywords: [],

    };
  },

  computed: {

    myKeywordIds() {
      return this.myKeywords.map((el) => {
        return el.id;
      });
    },

    displayableSuggestedKeywords() {
      return this.suggestedKeywords.filter((el) => {
        for (let i in this.myKeywordIds) {
          if (el.id === this.myKeywordIds[i]) {
            return false;
          }
        }

        return true;
      });
    }

  },

  watch: {
    keywordInput(newValue, oldValue) {

      if (newValue.length > 1) {
        this.setStillLoading(true);
        this.search(this.setStillLoading, newValue, this);
      } else {
        this.suggestedKeywords = [];
      }
    },

    keywords: {
      handler: function () {
        this.init();
      },
      deep: true,
      immediate: true
    },

  },

  methods: {
    getKeywordkey(kw) {

      let k = kw.id;

      if (kw.pivot !== undefined && kw.pivot.relevance !== undefined) {
        k = k + 'r' + kw.pivot.relevance;
      } else {
        k = k + 'un';
      }

      return k;
    },

    setStillLoading(isLoading) {
      this.stillLoading = isLoading;
    },


    // _.debounce is a function provided by lodash to limit how
    // often a particularly expensive operation can be run.
    // To learn
    // more about the _.debounce function (and its cousin
    // _.throttle), visit: https://lodash.com/docs#debounce
    search: _debounce((loading, search, vm) => {

      // Split search into multiple keyword-searches by COMMA and SEMICOLON
      const multiKeywordParts = search.split(/\s*[,;]\s*/);

      vm.$store.dispatch('keywords/searchMultiple',
          multiKeywordParts.map((searchText) => {
            return {searchText, limit: 40};
          }))
        .then(({keywords}) => {
          vm.suggestedKeywords = keywords;
        })
        .catch((err) => {
          console.error(err);
        })
        .then(() => {
          // Always
          loading(false);
        });

    }, 250),


    requestCreateNewKeyword() {

      if (this.keywordInput.length < 3) {
        return;
      }

      this.createNewKeyword(this.keywordInput);
      this.keywordInput = '';
    },

    createNewKeyword(title) {

      let promise;

      if (this.materialId) {
        promise = this.$store.dispatch('keywords/createAndAssign', {
          title,
          type: 'key',
          materialId: this.materialId,
          relevance: RELEVANCE_USER_MAX,
        });
      } else {
        console.info('Missing Material ID: Association is not stored remotely!');
        promise = this.$store.dispatch('keywords/create', {
          title,
          type: 'key',
        }).then((kw) => {
          if (kw && (!kw.pivot || !kw.pivot.relevance)) {
            kw.pivot = {
              relevance: RELEVANCE_USER_MAX,
            }
          }
          return kw;
        });
      }

      promise.then((keyword) => {
        this.insertOrUpdateKeyword(keyword);
      })

    },

    requestAddKeyword(kw) {

      if (this.myKeywords.find((el) => {
        return el.id === kw.id;
      }) === undefined) {

        if (kw && (!kw.pivot || !kw.pivot.relevance)) {
          kw.pivot = {
            relevance: RELEVANCE_USER_MAX,
          }
        }

        // Reset KeywordInput if after adding THIS keyword nothing is suggested anymore
        if (this.displayableSuggestedKeywords.length <= 1) {
          this.keywordInput = '';
        }

        if (this.materialId) {
          this.$store.dispatch('keywords/updateRelevance', {
            materialId: this.materialId,
            keywordId: kw.id
          }).then((data) => {
            this.insertOrUpdateKeyword(data);
          });
        } else {
          console.info('Missing Material ID: Association is not stored remotely!');
          this.insertOrUpdateKeyword(kw);
        }


      }

    },

    insertOrUpdateKeyword(kw) {
      let foundIndex = this.myKeywords.findIndex((el) => {
        return el.id === kw.id;
      });

      if (foundIndex >= 0) {
        this.myKeywords.splice(foundIndex, 1, kw);
        // this.$set(this.myKeywords, foundIndex, kw)
      } else {
        this.myKeywords.push(kw);
      }

      this.emitUpdate();
    },

    keywordRemoved(kw) {
      let foundIndex = this.myKeywords.findIndex((el) => {
        return el.id === kw.id;
      });

      if (foundIndex >= 0) {
        this.myKeywords.splice(foundIndex, 1);
        this.emitUpdate();
      }
    },

    /** i.e. pivot or something like that */
    keywordUpdated(kw, index) {
      this.emitUpdate();
    },

    btnRemoveAllKeywords() {

      // Confirm first
      const count = this.myKeywords.length;

      if (count > 3 && this.materialId) {
        if (confirm(this.$t('pool.Really-delete-count-keywords', {count: count})) === false) {
          return;
        }
      }

      // Do DB stuff
      const prom = new Promise((resolve, reject) => {

        if (this.materialId) {
          return this.$store.dispatch('keywords/deleteMultipleAssignemts',
              {
                keywordIds: this.myKeywords.map(bv => bv.id),
                materialId: this.materialId
              })
                     .then((response) => {
                       resolve(response);
                     })
                     .catch((response) => {
                       reject(response);
                     })
        } else {
          resolve('');
        }

      });

      // Do local and updates
      prom.then(() => {
        this.myKeywords.splice(0, this.myKeywords.length);
        this.emitUpdate();
      });

      // Focus Input-Element
      this.$refs.myInput.focus();

    },

    emitUpdate() {
      this.$emit('updated', this.myKeywords);
    },

    init() {

      // Deep Copy Keywords
      this.myKeywords = cloneDeep(this.keywords); // JSON.parse(JSON.stringify(this.keywords));

    }

  },

  components: {
    MaterialpoolSpinner,
    Keyword,
    eraseSvg
  }
}
</script>


<style scoped>
.spinnerBlock {
  width: 6em;
}

.icon-erase {
  height: 1.5em;
}

.icon-erase >>> path {
  fill: white;
}

.fade-enter-active, .fade-leave-active {
  transition: opacity .5s;
}

.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */
{
  opacity: 0;
}
</style>