<template>
  <b-modal size="lg"
           :title="$t('pool.Delete-material')"
           lazy
           ref="myModal"
           @hide="_cancelPromise"
  >
    <template #modal-footer>

      <b-button v-if="canDeleteMaterial && !materialIsReloading && material && material.resources.length === 0"
                variant="danger" size="sm" @click="_deleteThisMaterial">
        {{ $t('pool.material-delete') }}
      </b-button>

      <button type="button" class="btn btn-primary btn-sm" @click="hide">
        {{ $t('pool.Ok') }}
      </button>

    </template>

    <b-alert fade
             :show="materialIsReloading"
             variant="warning">
      <materialpool-spinner/>
      {{ $t('pool.Material-is-reloading') }}
    </b-alert>

    <div class="alert alert-warning" role="alert"
         v-if="!materialIsReloading && material && material.resources.length > 0">
      <strong>{{ $t('pool.attention') }}!</strong><br/>
      {{ $tc('pool.material-assigned-resources', material.resources.length, {count: material.resources.length}) }}
    </div>

    <div class="alert alert-success" role="alert"
         v-if="!materialIsReloading && material && material.resources.length === 0">
      <strong>{{ $t('pool.Perfect') }}!</strong><br/>
      {{ $tc('pool.material-assigned-resources', material.resources.length, {count: material.resources.length}) }}
    </div>

    <div class="assignedResources">
      <hr>

      <div class="row resource py-2" v-for="r in resources" :key="r.id">
        <div class="col col-2 col-md-1">{{ r.id }}</div>
        <div class="col col-4 col-md-5 ">
          <div v-if="r.original_filename" class="filename">{{ r.original_filename }}</div>
          <div v-if="r.notes" class="notes">{{ r.notes }}</div>
        </div>
        <div class="col col-6" v-if="r.materials">

          <div class="alert mb-1" :class="{
                    	'alert-danger' : r.materials.length <= 1,
                    	'alert-success' : r.materials.length > 1
                    }" role="alert">
            {{
              $tc('pool.material-other-assigned-material-pl', r.materials.length - 1, {
                count:
                r.materials.length
              })
            }}
          </div>

          <b-button variant="primary" size="sm" :to="{name: 'resource-detail', params:{id:r.id}}">
            {{ $t('pool.open') }}
          </b-button>

          <b-button variant="warning" size="sm"
                    v-if="canUpdateMaterial && canDeleteResource(r) && r.materials.length === 1"
                    @click="_detachAndDeleteResource(r)"
          >{{ $t('pool.detach-and-delete') }}
          </b-button>

          <b-button v-if="canUpdateMaterial" :variant="r.materials.length === 1 ? 'danger' : 'warning'" size="sm"
                    @click="_detachResourceFromMaterial(r)"
          >{{ $t('pool.detach') }}
          </b-button>

        </div>
        <div class="col" v-if="resourcesAreReloading">
          <materialpool-spinner/>
        </div>
      </div>

    </div>

  </b-modal>
</template>
<script>

import {BAlert, BButton, BModal} from '@/adapters/bootstrap';
import MaterialpoolSpinner                   from "../../spinner/materialpool-spinner";
import {savingDialogs}                       from "../../../helper/flashMessages";
import {useMaterialsStore}                   from '../../../apps/main/stores/materials';
import {useResourcesStore}                   from '../../../apps/main/stores/resources';
import {userCanManageOwnOrAll}                from '../../../apps/main/authorization';

export default {
  name: "materialDeletor",

  mixins: [savingDialogs],

  data() {
    return {
      reject: null,
      resolve: null,

      materialIsReloading: false,
      resourcesAreReloading: false,
    };
  },

  props: {
    materialId: {
      type: Number,
      required: true,
    },
    authorization: {
      type: Object,
      required: true,
    },
  },


  watch: {},

  computed: {

    canDeleteMaterial() {
      return userCanManageOwnOrAll(this.authorization, this.material, 'materials.delete-own', 'materials.delete-all');
    },

    canUpdateMaterial() {
      return userCanManageOwnOrAll(this.authorization, this.material, 'materials.update-own', 'materials.update-all');
    },

    /**
     * Gibt im besten Fall die Resourcen mit Relations zurück, ansonsten nur die Material-Resourcen (ohne Relations) oder ein leeres Array
     */
    resources() {
      if (this.resourcesWithRelations !== null && this.resourcesAreReloading === false) {
        // console.log('1', this.resourcesWithRelations);
        return this.resourcesWithRelations;
      } else if (this.material !== null) {
        // console.log('2')
        return this.material.resources;
      } else {
        // console.log('3')
        return [];
      }
    },

    modalIsOpen() {
      return this.reject !== null;
    }
  },

  asyncComputed: {

    material: {

      get() {
        if (!this.modalIsOpen) {
          return null;
        }

        this.materialIsReloading = true;

        // Reload
        return useMaterialsStore().getMaterialById(this.materialId)
                   .catch((message) => {
                     this.flashActionFailed(message);
                   })
                   .then((material) => {
                     this.materialIsReloading = false;
                     return material;
                   });
      },
      default: null,
      lazy: true,
    },

    resourcesWithRelations: {
      get() {
        if (!this.modalIsOpen) {
          return null;
        }

        this.resourcesAreReloading = true;

        const materialIds = (this.material === null) ? [] : this.material.resources.map(({id}) => id);
        return useResourcesStore().getMultiple(materialIds)
                   .catch((message) => {
                     this.flashActionFailed(message);
                   })
                   .then((resources) => {
                     this.resourcesAreReloading = false;
                     return resources;
                   });


      },
      default: null,
      lazy: true,
    }

  },

  methods: {

    canDeleteResource(resource) {
      return userCanManageOwnOrAll(this.authorization, resource, 'resources.delete-own', 'resources.delete-all');
    },

    _detachAndDeleteResource(resource) {
      this._detachResourceFromMaterial(resource)
          .then(({resource}) => {
            if (resource.materials.length > 9) {
              this.flashActionFailed(this.$t('pool.resource-can-not-be-deleted.'));
            } else {
              const deleteFlash = this.flashActionStartedWaiting(this.$t('pool.Delete-resource'));

              return useResourcesStore().deleteResource(resource.id)
                         .catch((message) => {
                           this.flashActionFailed(message, deleteFlash);
                         })
                         .then(() => {
                           this.flashActionSuccessfullyFinished(this.$t('pool.resource-deleted'), deleteFlash);
                         });
            }
          });
    },

    _detachResourceFromMaterial(resource) {

      const detachingFlash = this.flashActionStartedWaiting(this.$t('pool.Detach-resource'));

      return useMaterialsStore().detachResource(
          {materialId: this.materialId, resourceId: resource.id})
                 .catch((message) => {
                   this.flashActionFailed(message, detachingFlash);
                 })
                 .then((data) => {
                   this.flashActionSuccessfullyFinished(this.$t('pool.Detach-resource'), detachingFlash);
                   this.$asyncComputed.material.update();
                   return data;
                 });
    },

    _deleteThisMaterial() {
      if (this.material === null) {
        console.error('Material Information not loaded yet');
      } else if (this.material.resources.length > 0) {
        this.flashActionFailed(this.$tc('pool.material-cant-be-deleted-xy-resources-left', this.material.resources.length, {xy: this.material.resources.length}));
      } else {
        const deleteFlash = this.flashActionStartedWaiting(this.$t('pool.material-delete'));

        useMaterialsStore().deleteMaterial(this.materialId)
            .then(() => {
              this.flashActionSuccessfullyFinished(this.$t('pool.material-deleted'), deleteFlash);
            })
            .catch((message) => {
              this.flashActionFailed(this.$t('pool.material-delete-error') + ': ' + message, deleteFlash);
            })
            .then(() => {
              this._successfulPromise();
            });
      }
    },

    showPromise() {

      this._onReset();

      return new Promise((resolve, reject) => {
        this.resolve = resolve;
        this.reject  = reject;

        this.$refs.myModal.show();
      });

    },

    _successfulPromise() {
      if (typeof this.resolve === 'function') {
        this.resolve('material deleted');
      }

      this.$refs.myModal.hide();
    },

    _cancelPromise() {

      if (typeof this.reject === 'function') {
        this.reject('closed early');
      }

      // this.$refs.myModal.hide();
      this.resolve = null;
      this.reject  = null;

    },

    hide() {
      this.$refs.myModal.hide();
    },

    _onSubmit() {
      this.$refs.myModal.hide();
    },

    _onReset() {
      this.materialIsReloading   = false;
      this.resourcesAreReloading = false;

      // Clear Cache
      useMaterialsStore().clearMaterial(this.materialId);
    }
  },

  components: {
    MaterialpoolSpinner,
    BModal,
    BButton,
    BAlert
  }


}
</script>

<style scoped lang="scss">
@use "resources/sass/theme" as *;

.assignedResources {
  .resource {

    .filename {
      font-weight: bold;
    }

    .notes {
      font-size: 0.8em;
      color: $notes-font-color;
    }

    &:hover {
      background-color: $gray-200;
    }
  }
}

ul {
  padding-left: 0;
}
</style>
