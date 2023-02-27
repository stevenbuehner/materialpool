<template>
  <div class="resourcePreview card" @mouseover="hovered = true" @mouseleave="hovered = false">
    <component
        :is="previewComponent"
        :resource="resource"
        :hovered="hovered"
        @preview-zoom-request="openImagePreviewZoomer"/>

    <transition name="fade">
      <div class="card-body resourcePreviewMenu pt-2" v-if="hovered || isMobile">

        <div class="meta">
          <div class="filename pb-2" v-if="resource.original_filename">
            {{ resource.original_filename }}
          </div>

          <div v-if="resource.creator" class="pb-2">
            {{ $t('pool.Creator') }}:
            <user-name :user="resource.creator"/>
          </div>
        </div>

        <slot name="buttons">
          <slot name="default-buttons">
            <a v-if="showDownload"
               class="btn btn-sm btn-outline-primary mb-1"
               :href="downloadResourceLink(resource)">{{ $t('pool.download') }}</a>
            <router-link v-if="showOpen" :to="{name:'resource-detail', params: {id: resource.id}}"
                         class="btn btn-sm btn-outline-primary mb-1">{{ $t('pool.open') }}
            </router-link>
            <router-link v-if="resource.type==='pdf' || resource.type==='doc'"
                         :to="routerEditLimitationObject(resource)"
                         class="btn btn-sm btn-outline-primary mb-1">{{ $t('pool.page-assignments') }}
            </router-link>
          </slot>
          <slot name="additional-buttons"/>
        </slot>
      </div>

    </transition>

    <image-zoom v-if="previewZoom.show"
                :data="previewZoom.data"
                :start="previewZoom.start"
                :endless="true"
                @image-zoom:hiding="resetImageZoom"
                ref="imageZoom"/>

  </div>
</template>

<script>
import imagePreview                  from './image-preview.vue'
import textPreview                   from './text-preview.vue'
import pdfPreview                    from './pdf-preview.vue'
import audioPreview                  from './video-preview.vue'
import videoPreview                  from './video-preview.vue'
import docPreview                    from './doc-preview.vue'
import filePreview                   from './file-preview.vue'
import resPreview                    from './res-preview.vue'
import resourceLinks                 from '../resource-links.mixin';
import UserName                      from "../../user/user-name";
import {getOrderedPreviewZoomImages} from "../resource-preview-zoom";
import ImageZoom                     from "../../modals/imageZoom";
import {isTouch}                     from "../../../helper/mobileHelper";


export default {

  name: 'ResourcePreview',

  mixins: [resourceLinks],

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
  },

  data() {
    return {
      hovered: false,
      previewZoom: {
        show: false,
        data: [],
        start: 0
      }
    }
  },

  computed: {
    isMobile(){
      return isTouch;
    },

    previewComponent() {
      return this.resource.type + '-preview';
    }
  },

  methods: {
    openImagePreviewZoomer(emittingComponent) {
      const {data, start}    = getOrderedPreviewZoomImages(emittingComponent);
      this.previewZoom.data  = data;
      this.previewZoom.start = start;
      this.previewZoom.show  = true;

      this.$nextTick(() => {
        this.$refs.imageZoom.show();
      });
    },

    resetImageZoom() {
      this.previewZoom = {
        show: false,
        data: [],
        start: 0
      };
    }
  },


  components: {
    ImageZoom,
    UserName,
    imagePreview,
    textPreview,
    pdfPreview,
    audioPreview,
    videoPreview,
    docPreview,
    resPreview,
    filePreview,
  }
}
</script>

<style lang="scss">
@import "resources/sass/theme";

.resourcePreview {
  .fade-enter-active, .fade-leave-active {
    transition: opacity .5s;
  }

  .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */
  {
    opacity: 0;
  }

  .resourcePreviewMenu {
    position: absolute;
    margin-top: -5px;
    top: 100%;
    background: white;
    z-index: 10;
    border: 1px solid rgba(0, 0, 0, 0.125);
    border-top: none;
    border-radius: 0.25rem;
    width: calc(100% + 2px);
    margin-left: -1px;
  }

  .meta {
    .filename {
      font-size: .8em;
      color: $notes-font-color;
    }
  }
}


</style>