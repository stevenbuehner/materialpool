<template>
  <div class="card">

    <!-- HEADLINE AND GENERAL BUTTONS-->
    <div class="card-header d-flex flex-row justify-content-between">
            <span class="mt-auto">
                {{ $t('pool.Bibleverses') }}
            </span>

      <button class="btn btn-sm btn-danger"
              :title="$t('pool.remove-all')"
              v-if="myBibleverses.length > 0"
              type="button"
              @click="btnRemoveAllBibleverses">
        <erase-svg class="icon-erase"/>
      </button>
    </div>


    <!-- ALREADY EXISTING BIBLEVERSES-->
    <div class="card-body">
      <bibleverse v-for="(bv, index) in myBibleverses"
                  :key="getBibleverseKey(bv)"
                  v-model="myBibleverses[index]"
                  :material-id="materialId"
                  :removeable="!disabled"
                  :editable="!disabled"
                  :searchable="!disabled"
                  :dragable="!disabled && enableRelevance"
                  @removed="bibleverseRemoved"
                  @saved="bibleverseUpdated(bv, index); flashSaved('Bibleverse')"
                  @saving="flashStartSaving('Bibleverse')"
                  @savingPivot="flashStartSaving('Bibleverse Piot')"
                  @savingError="flashUpdateTagError"
                  @savingPivotError="flashUpdateTagError"
      ></bibleverse>
    </div>


    <!-- SEARCH INPUT -->
    <div class="card-body" v-if="!disabled">

      <div class="input-group">
        <input class="form-control"
               type="text"
               ref="myInput"
               :placeholder="$t('pool.Insert-bibleverse-here')"
               @keyup.enter="requestAddBibleverseAfterPromise"
               v-model="searchInput">

        <div class="input-group-append" v-if="stillLoading">
          <button
              class="btn btn-outline-secondary spinnerBlock"
              type="button">
            <materialpool-spinner/>
          </button>

        </div>

      </div>

    </div>


    <!-- SEARCH SUGGESTIONS -->
    <div class="card-footer" v-if="(displayableSuggestedBibleverses.length > 0 || stillLoading) && !disabled">
      <span v-if="stillLoading">{{ $t('pool.Looking-for-suggestions') }}</span>
      <h4 v-if="displayableSuggestedBibleverses.length > 0">
        {{ $t('pool.Bibleversesuggestions') }}:</h4>

      <transition-group name="fade" v-if="!stillLoading">
        <button type="button"
                class="btn btn-outline-secondary btn-sm mr-1 mb-1"
                v-for="bv in displayableSuggestedBibleverses"
                :key="'s' + getBibleverseKey(bv)"
                @click="addBibleverseClick(bv)"
        >
          <span class="icon" :style="{backgroundImage: 'url('+ bv.icon+')'}"></span>
          {{ bv.label }}
        </button>
      </transition-group>
    </div>

    <!-- EXTERNAL SUGGESTIONS -->
    <div class="card-footer" v-if="(displayableExternalSuggestions.length > 0) && !disabled">
      <h4>{{ $t('pool.System-Suggestions') }}:</h4>

      <transition-group name="fade">
        <button type="button"
                class="btn btn-outline-secondary btn-sm mr-1 mb-1"
                v-for="bv in displayableExternalSuggestions"
                :key="'es' + getBibleverseKey(bv)"
                @click="addBibleverseClick(bv)"
        >
          <span class="icon" :style="{backgroundImage: 'url('+ bv.icon+')'}"></span>
          {{ bv.label }}
        </button>
      </transition-group>
    </div>

  </div>
</template>

<script>

import bibleverse           from './biblevers.vue'
import eraseSvg             from '@icons/vendor/svg-icon/svg/zero/clear.svg';
import {RELEVANCE_USER_MAX} from "../../apps/config";
import _debounce            from 'lodash/debounce';
import MaterialpoolSpinner  from "../spinner/materialpool-spinner";
import {savingDialogs}      from "../../helper/flashMessages";
import {cloneDeep}          from "lodash";

export default {

  mixins: [savingDialogs],

  props: {
    bibleverses: {
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

    externalSuggestions: {
      type: Array,
      required: false,
      default() {
        return [];
      }
    },

    enableRelevance: {
      type: Boolean,
      default: true
    }

  },

  model: {
    prop: 'bibleverses',
    event: 'updated'
  },

  data() {
    return {
      myBibleverses: [],

      searchInput: '',

      stillLoading: false,
      suggestedBibleverses: [],
      showExternalSuggestions: true
    }
  },


  computed: {

    displayableSuggestedBibleverses() {
      return this.suggestedBibleverses.filter((el) => {
        return !this.isBibleverseAlreadyInSelection(el);
      });
    },

    displayableExternalSuggestions() {
      return this.externalSuggestions.filter((el) => {
        return !this.isBibleverseAlreadyInSelection(el);
      });
    }


  },

  watch: {
    searchInput(newValue, oldValue) {
      this.setStillLoading(true);
      this.search(this.setStillLoading, newValue, this);
    },

    bibleverses: {
      handler: function () {
        this.init();
      },
      deep: true,
      immediate: true
    },
  },


  methods: {

    isBibleverseAlreadyInSelection(bv) {
      for (let i in this.myBibleverses) {
        if (bv.from === this.myBibleverses[i].from && bv.to === this.myBibleverses[i].to) {
          return true;
        }
      }

      return false;
    },

    getBibleverseKey(bv) {

      let k = bv.label || bv.id;

      if (bv.pivot !== undefined && bv.pivot.relevance !== undefined) {
        k = k + 'r' + bv.pivot.relevance;
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

      vm.$store.dispatch('bibleverses/search', search)
        .then((bibleverses) => {
          vm.suggestedBibleverses = bibleverses;
        })
        .catch((data) => {
          alert(data);
        })
        .then(() => {
          // Always
          loading(false);
        });

    }, 250),


    addBibleverseClick(bv) {

      // Lösche das Input-Feld (und damit auch alle restlichen Vorschläge) nur dann, wenn nach diesem Bibelvers keine
      // weiteren existieren
      if (this.displayableSuggestedBibleverses.length == 1 && this.displayableSuggestedBibleverses[0] === bv) {
        this.searchInput = '';
      }

      this.addBibleverseToMaterial(bv);
    },

    addBibleverseToMaterial(bv) {

      return new Promise((resolve, reject) => {

        if (bv.id === undefined) {
          resolve(this.createNewBibleverse(bv.from, bv.to));
        } else {
          resolve(bv);
        }

      }).then((bibleverse) => {

        if (this.materialId) {
          return this.appendBibleverseToMaterial(bibleverse.id, this.materialId);
        } else {
          // console.info('Can not append bibleverse to material when materialId is missing!');
          return bibleverse
        }

      }).then((bibleverse) => {

        if (!bibleverse.pivot || !bibleverse.pivot.relevance) {
          bibleverse.pivot = {
            relevance: RELEVANCE_USER_MAX,
          }
        }

        this.myBibleverses.push(bibleverse);
        this.emitUpdate();

      })

    },

    requestAddBibleverseAfterPromise() {

      if (this.suggestedBibleverses.length > 0) {
        this.suggestedBibleverses.forEach((bibleverse) => {
          if (!this.isBibleverseAlreadyInSelection(bibleverse)) {
            this.addBibleverseToMaterial(bibleverse);
          }
        });
        this.suggestedBibleverses = [];
        this.searchInput          = '';
      }

    },

    createNewBibleverse(from, to) {
      return this.$store.dispatch('bibleverses/create', {from: from, to: to});
    },

    appendBibleverseToMaterial(bvId, materialId) {
      return this.$store.dispatch('bibleverses/updateRelevance', {
        bibleverseId: bvId,
        materialId,
        relevance: RELEVANCE_USER_MAX,
      })
    },

    bibleverseRemoved(bv) {
      let foundIndex = this.myBibleverses.findIndex((el) => {
        return el.id === bv.id;
      });

      if (foundIndex >= 0) {
        this.myBibleverses.splice(foundIndex, 1);
        this.emitUpdate();
      }
    },

    /** i.e. pivot or something like that */
    bibleverseUpdated(bv, index) {
      this.emitUpdate();
    },

    btnRemoveAllBibleverses() {

      const count = this.myBibleverses.length;

      if (count > 3 && this.materialId) {
        if (confirm(this.$t('pool.Really-delete-count-bibleverses', {count: count})) === false) {
          return;
        }
      }

      // Do DB stuff
      const prom = new Promise((resolve, reject) => {

        if (this.materialId) {
          return this.$store.dispatch('bibleverses/deleteMultipleAssignemts',
              {
                bibleverseIds: this.myBibleverses.map(bv => bv.id),
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
        this.myBibleverses.splice(0, this.myBibleverses.length);
        this.emitUpdate();
      });

      // Focus Input-Element
      this.$refs.myInput.focus();

    },

    emitUpdate() {
      this.$emit('updated', this.myBibleverses);
    },

    init() {

      // Deep Copy bibleverses
      this.myBibleverses = cloneDeep(this.bibleverses);//  JSON.parse(JSON.stringify(this.bibleverses));
    }

  },

  components: {
    MaterialpoolSpinner,
    bibleverse,
    eraseSvg,
  }

}
</script>

<style scoped>
.spinnerBlock {
  width: 6em;
}

.icon {
  position: relative;
  display: inline-block;
  background-size: contain;
  background-position: 0 0;
  height: 0.9rem;
  background-repeat: no-repeat;
  top: 0.1rem;
  width: 1rem;
  margin-right: 0.1rem;
  margin-left: 0;
}

.icon-erase {
  height: 1.5em;
}

.icon-erase :deep(path) {
  fill: white;
}

.fade-enter-active, .fade-leave-active {
  transition: all 1s, opacity .5s;
}

.fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */
{
  opacity: 0;
  transform: translateY(30px);
}

.fade-leave-active {
  position: absolute;
}
</style>
