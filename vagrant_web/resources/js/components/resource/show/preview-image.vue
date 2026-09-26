<template>
  <no-preview v-if="previewMissing"
              :resource="resource"
              :src="src"
              @regenerated="showRegeneratedPreview"/>
  <img v-else
       :key="previewUrl"
       :src="previewUrl"
       :alt="alt"
       v-image-queue
       @error="showMissingPreview"
       @q-error="showMissingPreview">
</template>

<script>
import NoPreview from './no-preview.vue';

export default {
  name: 'PreviewImage',

  components: {NoPreview},

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
      this.previewRevision = 0;
    }
  },

  methods: {
    showMissingPreview() {
      this.previewMissing = true;
    },

    showRegeneratedPreview() {
      this.previewRevision++;
      this.previewMissing = false;
    }
  }
}
</script>
