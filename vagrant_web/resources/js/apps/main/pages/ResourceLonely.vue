<template>
  <div class="container">
    <div class="sbResourceLonelyList materialpool-card-columns" v-if="resources.length > 0">
      <div class="card lonelyResource" v-for="r in resources" :key="r.id">
        <img :src="previewImage(r)" class="card-img-top" alt="No Resource Preview available">
        <div class="card-body">
          <h5 class="card-title">
            {{ r.type }} ({{ r.id }})
          </h5>
          <h6 class="card-subtitle" v-if="r.original_filename">
            {{ r.original_filename }}
          </h6>
          <p class="card-text">{{ r.notes }}</p>
          <router-link :to="{name: 'resource-detail', params:{id: r.id}}" class="btn btn-primary">
            {{ $t('pool.open') }}
          </router-link>
          <b-button v-if="canDeleteResource(r)" variant="danger" @click="btnDelete(r)">{{ $t('pool.delete') }}</b-button>
        </div>
      </div>
    </div>

    <div class="alert alert-info" v-if="isLoading">
      {{ $t('pool.Loading-resource') }}
    </div>
    <div class="alert alert-success" v-else-if="!isLoading && resources.length === 0">
      {{ $t('pool.Congratulations-No-lonely-Resources-found') }}
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
import {BButton, BPaginationNav} from '@/adapters/bootstrap';
import {previewImageFirstPage}   from "../../../components/serverRoutes";
import {useResourcesStore}       from '../stores/resources';
import {useGeneralStore}         from '../stores/general';
import {userCanManageOwnOrAll}   from '../authorization';

export default {
  name: "ResourceLonely",

  data() {
    return {
      page: 1,
      numPages: 1,
      total: 1,

      isLoading: true,
      refreshResources: 0,
      authorization: {id: null, is_admin: false, permissions: []},
    }
  },


  asyncComputed: {
    resources: {
      get() {
        this.isLoading = true;

        return useResourcesStore().lonely({page: this.page})
                   .then(({data, current_page, last_page, total}) => {
                     this.page      = current_page;
                     this.numPages  = last_page;
                     this.total     = total;
                     this.isLoading = false;
                     return data;
                   }).catch((message) => {
              alert(message);
            });
      },
      watch() {
        this.refreshResources;
      },
      default: []
    }
  },

  created() {
    useGeneralStore().currentUser().then(user => { this.authorization = user; });
  },

  methods: {
    canDeleteResource(resource) {
      return userCanManageOwnOrAll(this.authorization, resource, 'resources.delete-own', 'resources.delete-all');
    },
    linkGeneration(pageNum) {
      return {
        name: 'resource-lonely',
        query: {
          page: pageNum
        }
      }
    },

    previewImage(resource) {
      return previewImageFirstPage(resource);
    },

    btnDelete(resource) {
      if (confirm('Resource sicher löschen?')) {
        useResourcesStore().deleteResource(resource.id)
            .then(() => {
              this.refreshResources++;
            })
            .catch(() => {
              alert('Fehler beim löschen. Seite bitte neu laden!');
            });

      }

    }
  },

  components: {
    BPaginationNav,
    BButton
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
