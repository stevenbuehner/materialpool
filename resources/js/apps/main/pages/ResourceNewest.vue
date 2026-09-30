<template>
  <div class="container-fluid">
    <div class="sbResourceNewestList row" v-if="resources.length > 0">
      <div class="col col-xl-2 col-md-3 col-sm-4 col-6 pb-4" v-for="r in resources" :key="r.id">
        <resource-preview :resource="r"/>
      </div>
    </div>

    <div class="alert alert-info" v-if="isLoading">
      {{ $t('pool.Loading-resource') }}
    </div>
    <div class="alert alert-info" v-else-if="!hasError && resources.length === 0">
      {{ $t(poolEmpty ? 'pool.pool-empty' : 'pool.no-resources-found') }}
    </div>

    <b-pagination-nav
        v-if="total > 0"
        v-model="page"
        :limit="10"
        :number-of-pages="numPages"
        use-router
        :link-gen="linkGeneration"
        align="center">
    </b-pagination-nav>
  </div>
</template>

<script>
import {BPaginationNav}         from '@/adapters/bootstrap';
import ResourcePreview           from "../../../components/resource/show/resource-preview";
import {savingDialogs}           from "../../../helper/flashMessages";
import {useResourcesStore}       from '../stores/resources';
import {useSearchStore}          from '../stores/search';

export default {
  name: "ResourceNewest",

  mixins: [savingDialogs],

  data() {
    return {
      page: 1,
      numPages: 1,
      total: 1,

      isLoading: true,
      hasError: false,
      poolEmpty: false,
      refreshResources: 0,
    }
  },


  asyncComputed: {
    resources: {
      get() {
        this.isLoading = true;
        this.hasError = false;

        return useResourcesStore().find({
          order_by: 'id',
          order_dir: 'desc',
          page: this.page
        })
                   .then(async ({data, current_page, last_page, total}) => {
                     this.page      = current_page;
                     this.numPages  = last_page;
                     this.total     = total;
                     this.poolEmpty = false;
                     if (total === 0) {
                       try {
                         const {paging} = await useSearchStore().materials({query: [], page: 1});
                         this.poolEmpty = paging.total === 0;
                       } catch {
                         // The resource empty state remains accurate if the material check fails.
                       }
                     }
                     this.isLoading = false;
                     return data;
                   })
                   .catch((message) => {
                     this.hasError = true;
                     this.isLoading = false;
                     this.flashActionFailed(message);
                     return [];
                   });
      },
      watch() {
        this.refreshResources;
      },
      default: []
    }
  },

  methods: {
    linkGeneration(pageNum) {
      return {
        name: 'resource-newest',
        query: {
          page: pageNum
        }
      }
    },
  },

  components: {
    ResourcePreview,
    BPaginationNav
  }
}
</script>

<style scoped>
img {
  width: auto;
  height: auto;
  max-height: 20rem;
}
</style>
