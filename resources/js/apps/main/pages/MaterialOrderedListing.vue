<template>
  <div class="container">
    <h1>{{ $t('pool.' + titleKey) }}</h1>

    <searchbar-outcome
        v-if="!isLoading"
        :materialIds="materialIds"
    />

    <div class="d-flex justify-content-between align-items-center">
      <materialpool-spinner v-if="isLoading"/>
    </div>

    <b-alert variant="danger" :show="hasError">Error: {{ errorMessage }}</b-alert>
    <div v-if="!isLoading && !hasError && materialIds.length === 0" class="alert alert-info">
      {{ $t(poolEmpty ? 'pool.pool-empty' : 'pool.no-materials-found') }}
    </div>

    <hr>

    <b-pagination-nav
        v-if="paging.total > 0"
        v-model="paging.current_page"
        :limit="10"
        :number-of-pages="paging.last_page"
        use-router
        :link-gen="linkGeneration"
    />
  </div>
</template>

<script>
import {BAlert, BPaginationNav} from '@/adapters/bootstrap';
import MaterialpoolSpinner      from '../../../components/spinner/materialpool-spinner';
import SearchbarOutcome         from './search/searchbarOutcome.vue';
import {useSearchStore}         from '../stores/search';
import {useResourcesStore}      from '../stores/resources';

export default {
  name: 'MaterialOrderedListing',

  props: {
    orderBy: {
      required: true,
      type: String,
      validator: value => ['created_at', 'updated_at'].includes(value),
    },
    titleKey: {
      required: true,
      type: String,
    },
    page: {
      default: 1,
      type: Number,
    },
  },

  data() {
    return {
      materialIds: [],
      paging: {
        current_page: 1,
        last_page: 1,
        total: 0,
      },
      isLoading: true,
      hasError: false,
      errorMessage: '',
      poolEmpty: false,
    };
  },

  watch: {
    page: {
      handler() {
        this.updateMaterialList();
      },
      immediate: true,
    },
  },

  methods: {
    updateMaterialList() {
      this.isLoading = true;
      this.hasError = false;

      useSearchStore().materials({
        query: [],
        page: this.page,
        orderBy: this.orderBy,
      }).then(async ({materials, paging}) => {
        this.paging = paging;
        this.materialIds = materials.map(material => material.id);
        this.poolEmpty = false;
        if (paging.total === 0) {
          try {
            const resources = await useResourcesStore().find({page: 1});
            this.poolEmpty = resources.total === 0;
          } catch {
            // Keep the material-specific message when the second request fails.
          }
        }
      }).catch(message => {
        this.hasError = true;
        this.errorMessage = message;
        this.materialIds = [];
      }).then(() => {
        this.isLoading = false;
      });
    },

    linkGeneration(pageNum) {
      return {
        name: this.$route.name,
        query: {page: pageNum},
      };
    },
  },

  components: {
    BAlert,
    BPaginationNav,
    MaterialpoolSpinner,
    SearchbarOutcome,
  },
};
</script>
