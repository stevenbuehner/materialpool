<template>
  <div class="resourceDetail">

    <component
        :is="detailComponent"
        :resource="resource"
        class="detailContents"
        @resource-updated="$emit('resource-updated', $event)"/>


    <div class="container-fluid p-2">

      <div class="row mx-n2">

        <div class="col-12 col-md-6 pb-2 meta">
          <div class="notes" v-if="resource.notes && resource.notes.length > 0">Notiz: {{ resource.notes }}
          </div>
          <div class="originalFilename" v-if="resource.original_filename">
            {{ $t('pool.Filename') }}: {{ resource.original_filename }}

            <!-- Gesamtseitenanzahl des Dokuments nur anzeigen, wenn eine Limitierung existiert -->
            <span class="page_count"
                  v-if="resource.page_count && (resource.pivot && resource.pivot.limitation && resource.pivot.limitation.pages)">
                            ({{ resource.page_count }} {{ $tc('pool.Page', resource.page_count) }})
                        </span>
          </div>

          <!--
          <div class="creator">{{$t('pool.Creator-ID')}}: {{resource.created_by}}</div>
          -->

          <div class="filesize" v-if="resource.filesize">
            {{ $t('pool.Filesize') }}: {{ resource.filesize | readableBytes }}
          </div>

          <div class="resource-id">{{ $t('pool.Resource-ID') }}: {{ resource.id }}</div>
        </div>

        <div class="col-12 col-md-6">
          <slot name="buttons">
            <slot name="default-buttons">

              <a v-if="showDownload"
                 class="btn btn-outline-primary mb-1"
                 :href="downloadResourceLink(resource)">{{ $t('pool.download') }}</a>

              <router-link v-if="showOpen" :to="{name:'resource-detail', params: {id: resource.id}}"
                           class="btn btn-outline-primary mb-1">{{ $t('pool.open') }}
              </router-link>

              <router-link v-if="resource.type==='pdf' || resource.type==='doc'"
                           :to="routerEditLimitationObject(resource, resource.pivot)"
                           class="btn btn-outline-primary  mb-1">{{ $t('pool.page-assignments') }}
              </router-link>

              <router-link :to="{name:'resource-replace', params: {r1 : resource.id, r2 : null}}"
                           class="btn btn-outline-danger  mb-1">
                {{ $t('pool.replace-this-resource') }}
              </router-link>

              <button v-if="showDelete"
                      class="btn btn-outline-danger mb-1"
                      @click="btnDeleteResource(resource)"
                      :title="$t('pool.Delete-resource')">{{ $t('pool.delete') }}
              </button>
            </slot>
            <slot name="additional-buttons"/>
          </slot>
        </div>

      </div>

    </div>

  </div>
</template>

<script>
import imageDetail   from './image-detail.vue'
import textDetail    from './text-detail.vue'
import pdfDetail     from './pdf-detail.vue'
import audioDetail   from './video-preview.vue'
import videoDetail   from './video-preview.vue'
import docDetail     from './doc-detail.vue'
import resDetail     from './res-preview.vue'
import fileDetail    from './file-detail.vue'
import resourceLinks from '../resource-links.mixin';

import filesize from "../../../helper/filesize.mixin";

export default {

  name: "ResourceDetail",

  mixins: [resourceLinks, filesize],

  props: {
    resource: {
      required: true,
      type: Object
    },

    showDownload: {
      type: Boolean,
      required: false,
      default: true
    },

    showOpen: {
      type: Boolean,
      required: false,
      default: true
    },

    showDelete: {
      type: Boolean,
      required: false,
      default: true
    },
  },

  computed: {
    detailComponent() {
      return this.resource.type + '-detail';
    },

    limitationComponent() {
      return this.resource.type + '-limitation';
    }
  },

  methods: {
    btnDeleteResource(resource) {

      resource = resource || this.resource;

      if (resource.materials === undefined) {
        this.$store.dispatch('resources/get', this.resource.id)
            .then((resource) => {
              this.btnDeleteResource(resource);
            });
      } else if (resource.materials.length > 0) {
        alert('Löschen nicht möglich. Materialien sind noch zugewwiesen!')
      } else {
        this.$store.dispatch('resources/deleteResource', resource.id)
            .then(() => {
              this.$router.go(-1);
            });
      }

    }
  },


  components: {
    imageDetail,
    textDetail,
    pdfDetail,
    audioDetail,
    videoDetail,
    docDetail,
    resDetail,
    fileDetail,
  }
}
</script>

<style lang="scss">

.resourceDetail {
  .meta {
    color: grey;
    font-size: smaller;
  }
}
</style>