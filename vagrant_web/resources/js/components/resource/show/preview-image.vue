<template>
  <no-preview v-if="previewMissing"
              v-bind="$attrs"
              :resource="resource"
              :src="src"
              @regenerated="showRegeneratedPreview"/>
  <div v-else v-bind="$attrs" class="previewImage" :class="{isLoading: previewIsLoading}">
    <img :key="previewUrl"
         :src="previewUrl"
         :alt="alt"
         v-image-queue.hide
         @error="showMissingPreview"
         @q-queued="showLoadingPreview"
         @q-loading="showLoadingPreview"
         @q-loaded="showLoadedPreview"
         @q-error="showMissingPreview">

    <div v-if="previewIsLoading" class="previewImageLoading">
      <materialpool-spinner size="lg"/>
    </div>
  </div>
</template>

<script>
import MaterialpoolSpinner from '../../spinner/materialpool-spinner.vue';
import NoPreview from './no-preview.vue';

export default {
  name: 'PreviewImage',

  inheritAttrs: false,

  components: {MaterialpoolSpinner, NoPreview},

  props: {
    resource: {
      required: true,
      type: Object
    },

    src: {
      required: true,
      type: String
    },

    alt: {
      required: true,
      type: String
    }
  },

  data() {
    return {
      previewMissing: false,
      previewIsLoading: true,
      previewRevision: 0
    };
  },

  computed: {
    previewUrl() {
      if (this.previewRevision === 0) {
        return this.src;
      }

      const separator = this.src.includes('?') ? '&' : '?';

      return `${this.src}${separator}preview-revision=${this.previewRevision}`;
    }
  },

  watch: {
    src() {
      this.previewMissing = false;
      this.previewIsLoading = true;
      this.previewRevision = 0;
    }
  },

  methods: {
    showMissingPreview() {
      this.previewMissing = true;
      this.previewIsLoading = false;
    },

    showLoadingPreview() {
      this.previewIsLoading = true;
    },

    showLoadedPreview() {
      this.previewIsLoading = false;
    },

    showRegeneratedPreview() {
      this.previewRevision++;
      this.previewMissing = false;
      this.previewIsLoading = true;
    }
  }
}
</script>

<style scoped>
.previewImage {
  position: relative;
  overflow: hidden;

  &.isLoading {
    min-height: 12rem;
  }
}

.previewImage > img {
  display: block;
  width: 100%;
}

.previewImageLoading {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(255, 255, 255, .7);
  pointer-events: none;
}
</style>
