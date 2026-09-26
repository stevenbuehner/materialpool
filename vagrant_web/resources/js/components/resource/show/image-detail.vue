<template>

  <preview-image
      :resource="resource"
      :src="resourceImagePreviewUrl"
      :alt="resource.notes || ''"
      class="img-fluid d-block mx-auto"
      @click="goToResource"/>

</template>

<script>

import {previewImageFirstPage}                              from '../../serverRoutes';
import resourceLinks                                        from '../resource-links.mixin';
import {max_preview_image_size_x, max_preview_image_size_y} from "../../../apps/config";
import PreviewImage                                         from './preview-image.vue';

export default {

  name: 'ImageDetail',

  mixins: [resourceLinks],

  props:
      {
        resource: {
          required: true,
          type: Object
        },
        width: {
          required: false,
          default: max_preview_image_size_x
        },
        height: {
          required: false,
          default: max_preview_image_size_y
        }
      },

  computed: {

    title() {
      var title = 'Resource';

      if (this.resource.original_filename) {
        title = this.resource.original_filename;
      }

      return title
    },

    resourceImagePreviewUrl() {
      return previewImageFirstPage(this.resource, this.width, this.height);
    },

  },

  methods: {
    goToResource() {
      window.location.href = this.resourceUrl;
    }
  },
  components: {
    PreviewImage
  }
}
</script>

<style scoped>
.myCard {
  max-width: 20rem;
  cursor: pointer;
}
</style>
