<template>
  <div class="noPreview card-header text-center" role="status" @click.stop>
    <p class="mb-2">{{ $t('pool.Preview-not-available') }}</p>

    <button class="btn btn-sm btn-outline-primary"
            type="button"
            :disabled="isRegenerating"
            @click="regenerate">
      <materialpool-spinner v-if="isRegenerating" size="sm"/>
      <span v-if="isRegenerating" class="visually-hidden">{{ $t('pool.Preview-is-being-generated') }}</span>
      <span v-else>{{ $t('pool.Regenerate-preview') }}</span>
    </button>
  </div>
</template>

<script>
import MaterialpoolSpinner from '../../spinner/materialpool-spinner.vue';
import {isPreviewRegenerationActive, regeneratePreview} from '../preview-regeneration';

export default {
  name: 'NoPreview',

  components: {MaterialpoolSpinner},

  props: {
    resource: {
      required: true,
      type: Object
    },

    src: {
      required: true,
      type: String
    }
  },

  computed: {
    isRegenerating() {
      return isPreviewRegenerationActive(this.resource.id);
    },

    refreshUrl() {
      return this.withQueryParameter(this.src, 'refresh', '1');
    }
  },

  methods: {
    async regenerate() {
      let regenerated = false;

      try {
        regenerated = await regeneratePreview(this.resource.id, async () => {
          const response = await fetch(this.refreshUrl, {cache: 'no-store'});

          if (!response.ok || !response.headers.get('Content-Type')?.startsWith('image/')) {
            throw new Error('Preview could not be regenerated.');
          }
        });
      } catch (_error) {
        // Die Komponente bleibt sichtbar, damit ein erneuter Versuch möglich ist.
      }

      if (regenerated) {
        this.$emit('regenerated');
      }
    },

    withQueryParameter(url, name, value) {
      const separator = url.includes('?') ? '&' : '?';

      return `${url}${separator}${encodeURIComponent(name)}=${encodeURIComponent(value)}`;
    }
  }
}
</script>

<style scoped>
.noPreview {
  min-height: 10rem;
}
</style>
