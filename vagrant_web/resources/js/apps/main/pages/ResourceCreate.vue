<template>
  <div class="container resourceCreatePage">
    <h1>{{ $t('pool.create-new-resource') }}</h1>


    <div v-if="!materialCreationRunning">
      <resource-uploader
          @multiple-resources-created="onMultipleResourcesUploaded"
          :styleObject="{minHeight: '50vh'}">
        {{ $t('pool.drop-file-to-upload-resource') }}
      </resource-uploader>

      <div class="options mt-2">{{ $t('pool.Additional-Options') }}:</div>
      <b-form-checkbox
          :checked="autocreateMaterial"
          @input="autocreateMaterial = $event"
      >{{ $t('pool.auto-create-material') }}</b-form-checkbox>
    </div>

    <b-alert variant="info" :show="materialCreationRunning && !error">{{ $t('pool.material-is-beeing-generated') }}
    </b-alert>

    <b-alert variant="danger" :show="error">{{ $t('pool.Errormessage') }}:
      {{ error }}
      <button class="btn btn-danger btn-sm float-end" @click="$router.go()">{{ $t('pool.Reload-page') }}</button>
    </b-alert>

  </div>
</template>

<script>

import ResourceUploader        from "../../../components/uploader/resourceUploader";
import {BAlert, BFormCheckbox} from '@/adapters/bootstrap';

export default {
  name: "resourceUpload",

  data() {
    return {
      autocreateMaterial: true,
      materialCreationRunning: false,
      error: null
    };
  },

  methods: {

    onMultipleResourcesUploaded(resources) {

      if (this.autocreateMaterial === true) {

        this.materialCreationRunning = true;

        this.$store.dispatch('resources/autoCreateMaterial', {resourceIds: resources.map((r) => r.id)})
            .then((material) => {

              this.$router.push({
                name: 'material-detail',
                params: {
                  id: material.id
                }
              });

            })
            .catch(({message}) => {
              this.error = message;
            });
      }
    },

  },
  components: {
    ResourceUploader,
    BFormCheckbox,
    BAlert
  }
}
</script>

<style lang="scss">

.resourceCreatePage {
  .options {
    font-weight: bold;
  }
}

</style>
