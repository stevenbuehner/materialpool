<template>
  <div class="container-fluid border rounded p-3">

    <div class="beforeLoaded d-flex flex-column justify-content-around " v-if="!keyword">
            <span class="align-self-center d-flex flex-column justify-content-center">
                <materialpool-spinner class="align-self-center"/>
            {{ $t('pool.Keyword-is-beeing-loaded') }}
            </span>

    </div>

    <div class="form" v-if="keyword">
      <div class="row">
        <div class="col-sm-3">
          <label>{{ $t('pool.Title') }}:</label>
        </div>
        <div class="col-sm-9">
          <b-form-input
              name="keywordText"
              id="keywordText"
              type="text"
              v-model="keyword.title"
              :disabled="disableForm"
              autofocus
          ></b-form-input>
          <div class="meta_data">ID: {{ keyword.id }}, Key: {{ keyword.lc_title }}</div>
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-sm-3">
          <label>{{ $t('pool.Select-Type') }}:</label>
        </div>
        <div class="col-sm-9">
          <b-form-select
              name="keywordType"
              id="keywordType"
              v-model="keyword.type"
              :disabled="disableForm"
              @keydown.enter.prevent="btnSave"
          >
            <option value="key">Keyword</option>
            <option value="person">Person</option>
            <option value="place">Place</option>
            <option value="lang">Language</option>
          </b-form-select>
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-sm-3">
          <label>{{ $t('pool.Select-Icon') }}:</label>
        </div>
        <div class="col-sm-9">
          {{ $t('pool.Coming-soon') }}
        </div>
      </div>

      <div class="row mt-2">
        <div class="col-sm-3">
          <label>{{ $t('pool.Info') }}:</label>
        </div>
        <div class="col-sm-9">
          <materialpool-spinner class="align-self-center" v-if="relationsCount === null"/>

          <div v-if="relationsCount">
            {{ $t('pool.Keyword_usage', relationsCount) }}
          </div>

        </div>
      </div>

      <div class="row mt-2">
        <div class="col-sm-3">
          <label>{{ $t('pool.Direct-Parent') }}:</label>
        </div>
        <div class="col-sm-9" v-if="keyword && keyword.parent_id && !parent">
          {{ $t('pool.Loading-parent-keyword') }}
        </div>
        <div class="col-sm-9" v-else-if="parent">
          <router-link
              :to="{name:'keyword-detail', params:{id: keyword.parent_id}}">
            <keyword
                :keyword="parent"
                :editable="false"
                :removeable="false"
            ></keyword>
          </router-link>
        </div>
        <div class="col-sm-9" v-else>
          {{ $t('pool.none') }}
        </div>
      </div>

      <div class="row mt-2">
        <slot name="menu">
          <div class="col-sm-3">
          </div>
          <div class="col-sm-9 menu">
            <slot name="all-buttons">
              <b-button variant="primary"
                        :disabled="!keywordWasModified || disableForm"
                        @click.prevent="btnSave">{{ $t('pool.save') }}
              </b-button>
              <slot name="additional-buttons"/>
              <b-button variant="danger"
                        :disabled="disableForm"
                        @click.prevent="btnDelete">{{ $t('pool.delete') }}
              </b-button>
            </slot>
          </div>
        </slot>
      </div>
    </div>
  </div>
</template>

<script>
import {BButton, BFormInput, BFormSelect} from '@/adapters/bootstrap';
import Keyword                            from "./keyword";
import MaterialpoolSpinner                from "../spinner/materialpool-spinner";
import {useKeywordsStore}                 from '../../apps/main/stores/keywords';


export default {

  name: "keywordEdit",

  props: {
    id: {
      type: Number,
      required: true
    }
  },

  data() {
    return {
      keyword: null,

      backupJsonKeyword: null,
      errorOnLoadingMessage: null,
      keywordWasModified: false,

      disableForm: false,
    };
  },


  asyncComputed: {
    parent: {
      get() {
        if (this.keyword && this.keyword.parent_id) {
          return useKeywordsStore().get(this.keyword.parent_id);
        } else {
          return null;
        }
      },
      default: null,
      /* watch() {
                  this.forceReload
              }*/
    },

    relationsCount: {
      get() {
        return useKeywordsStore().relationsCount(this.id);
      },
      default: null,
    }
  },

  watch: {
    id(newValue) {
      this.keyword = null;
      this.getKeyword();
    },
    keyword: {
      handler: function (newVal, oldVal) {
        this.updateKeywordModified();
      },
      deep: true
    }
  },

  created() {
    this.getKeyword();
  },


  methods: {

    getKeyword() {
      this.errorOnLoadingMessage = null;

      useKeywordsStore().get(this.id).then((keyword) => {
        this.setKeyword(keyword);
        this.errorOnLoadingMessage = null;
      }).catch((response) => {
        this.errorOnLoadingMessage = response;
      });
    },

    setKeyword(keyword) {
      this.keyword           = keyword;
      this.backupJsonKeyword = JSON.stringify(keyword);
    },

    updateKeywordModified() {
      this.keywordWasModified = JSON.stringify(this.keyword) !== this.backupJsonKeyword;
    },

    btnSave() {

      // Ignore if nothing was changed
      if (this.keywordWasModified === false) {
        return;
      }

      this.disableForm = true;
      const originalK  = JSON.parse(this.backupJsonKeyword);
      let modifiedData = {};

      const mod = ['title', 'type', 'custom_icon'].filter((p) => {
        return originalK[p] !== this.keyword[p]
      }).forEach((p) => {
        modifiedData[p] = this.keyword[p];
      });

      this.updateKeywordData(modifiedData)
          .then(() => {
            // Always
            this.disableForm = false;
          });

    },

    btnDelete() {

      const answer = confirm(this.$t('pool.Are-you-shure-about-deleting-this-keyword-from-existance'));

      if (answer === true) {
        this.disableForm = true;

        useKeywordsStore().delete(this.id)
            .then((deletionConfirmed) => {
              this.$emit('deleted');
            })
            .catch((errorMessage) => {
              alert(errorMessage);
              this.disableForm = false;
            });
      }

    },

    updateKeywordData(properties) {

      this.$emit('saving', properties);

      const promise = useKeywordsStore().update({id: this.id, data: properties});

      promise.then((keyword) => {

        this.setKeyword(keyword);
        this.$emit('saved', keyword);

      }).catch((response) => {

        // on failure
        this.$emit('savingError', {
          tag: this.keyword, // "Tag" is used for bibleverses and keywords
          msg: this.parseResponseErrors(response.response)
        });
      });

      return promise;

    },

    parseResponseErrors(response) {
      let msg = 'Error! ';

      if (response.data && response.data.errors) {
        for (let i in response.data.errors) {
          msg += i + ': ' + response.data.errors[i] + '. ';
        }
      }

      return msg;
    },
  },

  components: {
    MaterialpoolSpinner,
    Keyword,
    BFormInput,
    BFormSelect,
    BButton
  }
}
</script>

<style scoped>
.beforeLoaded {
  min-height: 50vh;
}

.meta_data {
  font-size: smaller;
  color: #CCC;
  margin-left: 1em;
  margin-top: .25em;
}
</style>
