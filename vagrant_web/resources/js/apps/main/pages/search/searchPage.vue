<template>
  <div class="container">

    <searchbar-header
        @searchUpdated="searchInputChanged"
        :searchObjects="searchObjects"
    />

    <hr>

    <searchbar-outcome
        :materialIds="materialIds"
        v-if="!isLoading"
        :highlight-keywords="keywordIdToHighlight"
        :highlight-bibleverses="bibleverseRangesToHighlight"
    />

    <div class="d-flex justify-content-between align-items-center">
      <materialpool-spinner v-if="isLoading"/>
    </div>

    <b-alert variant="danger" :show="hasError">Error: {{ errorMessage }}</b-alert>

    <hr>

    <b-pagination-nav
        v-model="paging.current_page"
        :limit="10"
        :number-of-pages="paging.last_page"
        use-router
        :link-gen="linkGeneration"
        align="center">
    </b-pagination-nav>

  </div>
</template>

<script>
import searchbarHeader          from './searchbarHeader.vue';
import searchbarOutcome         from './searchbarOutcome.vue';
import {BAlert, BPaginationNav} from '@/adapters/bootstrap';

import {
  searchArrayItemsToSearchQuery,
  searchQueryStringToSearchQueryArray,
  searchQueryToSearchArrayObjects
}                          from "../../../../components/search/searchHelper";
import MaterialpoolSpinner from "../../../../components/spinner/materialpool-spinner";
import {useSearchStore}    from '../../stores/search';

export default {

  name: 'searchPage',

  props: {
    query: {
      type: String,
      default: ''
    },

    page: {
      required: false,
      default: 1,
      type: Number
    },

    quicksearch: {
      type: String,
      required: false,
      default: ''
    }
  },

  data() {
    return {
      materialIds: [],
      paging: {
        current_page: 1,
        from: 1,
        last_page: 1,
        next_page_url: null,
        per_page: 20,
        prev_page_url: null,
        to: 3,
        total: 3,
      },
      isLoading: false,

      searchObjects: {},

      hasError: false,
      errorMessage: '',
    };
  },

  computed: {

    // Nur Helper-Variable zur Überwachung im Watch-Statement
    queryAndPage() {
      return this.query + 'p' + this.page;
    },

    keywordIdToHighlight() {
      const kws = [];

      for (let i in this.searchObjects) {
        for (let j in this.searchObjects[i]) {
          if (this.searchObjects[i][j].item.type === "k") {
            kws.push(this.searchObjects[i][j].item.id);

            if (Array.isArray(this.searchObjects[i][j].descendants)) {
              this.searchObjects[i][j].descendants.forEach(function (kw) {
                kws.push(kw.id);
              });
            }
          }

        }
      }

      return kws;
    },

    bibleverseRangesToHighlight() {

      const bvs = [];

      for (let i in this.searchObjects) {
        for (let j in this.searchObjects[i]) {
          if (this.searchObjects[i][j].item.type === "b") {
            bvs.push({
              from: parseInt(this.searchObjects[i][j].item.from),
              to: parseInt(this.searchObjects[i][j].item.to)
            });
          }

        }
      }

      return bvs;
    }
  },

  watch: {
    queryAndPage: {
      handler() {
        this.updateMaterialList();
        searchQueryToSearchArrayObjects(this.query).then((searchObjects) => {
              this.searchObjects = searchObjects;
            }
        );
      },
      immediate: true
    }
  },

  methods: {

    searchInputChanged(searchLineItems) {
      const query = searchArrayItemsToSearchQuery(searchLineItems);

      if (query !== this.query) {
        this.$router.push({
          name: 'search',
          params: {
            search: query
          }
        });
      }

    },

    updateMaterialList() {

      const searchData = searchQueryStringToSearchQueryArray(this.query);
      this.isLoading   = true;
      this.hasError    = false;

      useSearchStore().materials({
        query: searchData,
        page: this.page
      }).then(({materials, paging}) => {
        this.paging      = paging;
        this.materialIds = materials.map(m => m.id);
      }).catch((message) => {
        this.hasError     = true;
        this.errorMessage = message;
        this.materialIds  = [];
      }).then(() => {
        // Always
        this.isLoading = false;
      });

    },

    linkGeneration(pageNum) {
      return {
        name: 'search',
        params: {
          search: this.query === "" ? false : this.query,
        },
        query: {
          page: pageNum,
        }
      }
    }

  },

  components: {
    MaterialpoolSpinner,
    searchbarHeader,
    searchbarOutcome,
    BPaginationNav,
    BAlert
  }
}
</script>

<style scoped>

</style>
