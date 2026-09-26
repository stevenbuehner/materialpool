<template>
  <no-preview v-if="previewMissing"
              v-bind="$attrs"
              :resource="resource"
              :src="src"
              @regenerated="showRegeneratedPreview"/>
  <div v-else v-bind="$attrs" class="previewImage" :class="{isLoading: previewLoadState !== 'loaded'}">
    <img :key="previewUrl"
         :src="previewUrl"
         :alt="alt"
         v-image-queue.hide
         @error="showMissingPreview"
         @q-queued="showQueuedPreview"
         @q-loading="showLoadingPreview"
         @q-loaded="showLoadedPreview"
         @q-error="showMissingPreview">

    <div v-if="previewLoadState === 'loading'" class="previewImageLoading">
      <materialpool-spinner size="lg"/>
    </div>

    <div v-else-if="previewLoadState === 'queued'"
         class="previewImageWaiting"
         :title="$t('pool.Preview-waiting-in-queue')">
      <history-icon aria-hidden="true"/>
      <span class="visually-hidden">{{ $t('pool.Preview-waiting-in-queue') }}</span>
    </div>
  </div>
</template>

<script>
import MaterialpoolSpinner from '../../spinner/materialpool-spinner.vue';
import NoPreview from './no-preview.vue';
import HistoryIcon from '@primer/octicons/build/svg/history.svg';

export default {
  name: 'PreviewImage',

  inheritAttrs: false,

  components: {HistoryIcon, MaterialpoolSpinner, NoPreview},

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
      previewLoadState: 'queued',
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
      this.previewLoadState = 'queued';
      this.previewRevision = 0;
    }
  },

  methods: {
    showMissingPreview() {
      this.previewMissing = true;
      this.previewLoadState = 'loaded';
    },

    showQueuedPreview() {
      this.previewLoadState = 'queued';
    },

    showLoadingPreview() {
      this.previewLoadState = 'loading';
    },

    showLoadedPreview() {
      this.previewLoadState = 'loaded';
    },

    showRegeneratedPreview() {
      this.previewRevision++;
      this.previewMissing = false;
      this.previewLoadState = 'queued';
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
  color: var(--bs-primary);
}

.previewImageLoading,
.previewImageWaiting {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: rgba(255, 255, 255, .7);
  pointer-events: none;
}

.previewImageWaiting {
  color: var(--bs-secondary-color);
  pointer-events: auto;
  cursor: help;
}

.previewImageWaiting :deep(svg) {
  width: 2.5rem;
  height: 2.5rem;
}
</style>
