<template>
  <div class="resource container">

    <div class="card" v-if="resource">

      <div class="card-header">
        <h1>{{ resource.original_filename || resource.type.toUpperCase() + '-Resource' }}</h1>
      </div>

      <b-tabs card>
        <b-tab title="Vorschau">
          <resource-detail :resource="resource" v-if="resource" :showOpen="false"
                           @resource-updated="onResourceUpdated"/>
        </b-tab>

        <b-tab title="Materialien" v-if="resource">
          <b-list-group v-if="resource">
            <b-list-group-item
                class="d-flex justify-content-between align-items-center"
                v-for="material in resource.materials"
                :key="material.id">

              <div class="materialData">
                {{ material.title }}
                <div class="limitation" v-if="isLimitable">
                  <component
                      :is="limitationComponent"
                      :pivot="material.pivot">
                  </component>
                </div>
              </div>

              <span class="materialNavi w-75 ps-lg-3 ps-1">
                <router-link v-if="material.pivot.limitation && isLimitable"
                             class="btn btn-warning btn-sm mb-1 me-1"
                             :to="routerEditLimitationObject(resource, material.pivot)">
                  {{ $t('pool.Edit-Limitation') }}</router-link>
                <router-link
                    v-if="!material.pivot.limitation && isLimitable"
                    class="btn btn-success btn-sm mb-1 me-1"
                    :to="routerEditLimitationObject(resource, material.pivot)">
                  {{ $t('pool.Create-Limitation') }}
                </router-link>
                <button @click="btnDetachMaterialFromResource(material)"
                        class="btn btn-outline-danger btn-sm mb-1 me-1">{{ $t('pool.remove') }}</button>
                <button @click="btnCopyMaterial(material)"
                        :title="$t('pool.Copy-material')"
                        class="btn btn-outline-danger btn-sm mb-1 me-1">{{ $t('pool.copy') }}</button>
                <router-link :to="{name: 'material-detail', params: {id: material.id}}"
                             class="btn btn-primary btn-sm mb-1 me-1">
                  {{ $t('pool.open') }}
                </router-link>
            </span>

            </b-list-group-item>
          </b-list-group>

          <div class="d-flex justify-content-center pt-2">
            <button class="btn btn-outline-secondary m-1"
                    :title="$t('pool.auto-create-material')"
                    v-if="resource.materials.length === 0"
                    @click="btnCreateAutoMaterialFromResource">
              <span class="icon autocreation"/>
            </button>
            <button class="btn btn-outline-secondary m-1"
                    :title="$t('pool.create-and-assign-material')"
                    @click="btnCreateAndAssignMaterialManually">
              <span class="icon manualcreation"/>
            </button>
            <button class="btn btn-outline-secondary m-1"
                    :title="$t('pool.assign-material')"
                    @click="btnAddMaterialToResource">
              <span class="icon assign"/>
            </button>
          </div>
        </b-tab>

        <b-tab title="MetaInfo" v-if="resource">
          <b-list-group>
            <b-list-group-item>
              <b>ID:</b> {{ resource.id }}
            </b-list-group-item>

            <b-list-group-item>
              <b>{{ $t('pool.Creator') }}:</b>
              <user v-if="resource.creator" :user="resource.creator"/>
              <span v-else>{{ $t('pool.Unknown') }}</span>
            </b-list-group-item>

            <b-list-group-item class="d-flex">
              <b>{{ $t('pool.Notes') }}:</b>&nbsp;
              <edditable type="span"
                         :value="resource.notes"
                         @value-changed="updateNotes"
                         :placeholder="$t('pool.Click-to-insert-a-note')"
                         class="flex-grow-1 ms-1"/>
            </b-list-group-item>

            <b-list-group-item>
              <b>{{ $t('pool.Created-at') }}:</b> {{ recentOrFormat(dayjs(resource.created_at)) }}
            </b-list-group-item>
            <b-list-group-item>
              <b>{{ $t('pool.Updated-at') }}:</b> {{ recentOrFormat(dayjs(resource.updated_at)) }}
            </b-list-group-item>

            <b-list-group-item>
              <b>{{ $t('pool.Content-Hash') }}:</b> {{ resource.content_hash || $t('pool.missing') }}
            </b-list-group-item>

            <b-list-group-item v-if="resource.filesize">
              <b>{{ $t('pool.Filesize') }}:</b> {{ readableBytes(resource.filesize) }}
            </b-list-group-item>

            <b-list-group-item class="d-flex">
              <b>{{ $t('pool.Web-URL') }}:</b>&nbsp;
              <edditable type="a"
                         :value="resource.remote_path"
                         @value-changed="updateRemotePath"
                         :placeholder="$t('pool.Click-to-insert-an-URL')"
                         :key="forceReload"
                         class="flex-grow-1 ms-1"/>
            </b-list-group-item>
            <b-list-group-item>
              <b>{{ $t('pool.Publicity') }}:</b>
              <toggle :value="resource.is_public" id="is_public" type="light"
                      style="font-size: .6em; position: relative; top: .4em;"
                      :key="forceReload"
                      @isToggled="updateIsPublic"/>
              {{ resource.is_public ? $t('pool.Resource-is-public') : $t('pool.Resource-is-private') }}
            </b-list-group-item>

            <b-list-group-item v-if="resource.original_filename">
              <b>{{ $t('pool.Original-Filename') }}:</b> {{ resource.original_filename }}
            </b-list-group-item>
            <b-list-group-item v-if="resource.page_count">
              <b>{{ $t('pool.Page-Count') }}:</b> {{ resource.page_count }}
            </b-list-group-item>

          </b-list-group>
        </b-tab>
      </b-tabs>
    </div>

    <b-alert :show="isLoading" variant="info">{{ $t('pool.Loading-resource') }}</b-alert>

    <b-alert :show="!!errorMsg" variant="danger">{{ $t('pool.Errormessage') }}: {{ errorMsg }}</b-alert>

    <material-selector ref="materialSelector"/>

    <material-creator ref="materialCreator"/>

    <custom-dialog ref="myDialog"/>

  </div>
</template>

<script>
import {BAlert, BListGroup, BListGroupItem, BTab, BTabs} from '@/adapters/bootstrap';
import pdfLimitation
                                                         from '../../../components/resource/limitation/pdfLimitation.vue';
import audioLimitation
                                                         from '../../../components/resource/limitation/audioLimitation.vue';
import videoLimitation
                                                         from '../../../components/resource/limitation/videoLimitation.vue';
import {isResourceTypeLimitable}                         from "../../../components/resource/limitation/limitationHelper";
import resourceDetail                                    from '../../../components/resource/show/resource-detail'

import user             from '../../../components/user/user-name';
import MaterialSelector from "../../../components/modals/selectors/materialSelector";
import CustomDialog     from "../../../components/modals/dialogs/customDialog";
import MaterialCreator  from "../../../components/modals/creators/materialCreator";
import Toggle           from "../../../components/general/toggle";
import Edditable        from "../../../components/general/edditable";

// Mixins
import resourceLinks         from '../../../components/resource/resource-links.mixin';
import {formatLocalizedDate} from "../../../helper/datetime.mixin";
import {savingDialogs}       from "../../../helper/flashMessages";
import filesize              from "../../../helper/filesize.mixin";


export default {

  name: "resourceApp",

  mixins: [resourceLinks, formatLocalizedDate, savingDialogs, filesize],

  props: {
    id: {
      required: true,
      type: Number
    }
  },

  data() {
    return {
      isLoading: false,
      errorMsg: null,
      resource: null,

      forceReload: 0,
    };
  },

  watch: {
    id: {
      immediate: true,
      handler() {
        this.loadResource();
      },
    },
    forceReload() {
      this.loadResource();
    },
  },

  computed: {
    limitationComponent() {
      return this.resource ? this.resource.type + 'Limitation' : false;
    },

    isLimitable() {
      return isResourceTypeLimitable(this.resource.type);
    },

    metaInfo() {

      let info = {};

      for (let i in this.resource) {
        if (i !== 'created_by' && this.resource[i] !== '' && i !== 'type') {
          info[i] = this.resource[i];
        }
      }

      return info;
    }
  },

  methods: {
    loadResource() {
      this.isLoading = true;

      return this.$store.dispatch('resources/get', this.id)
          .then((data) => {
            this.resource = data;
            this.errorMsg = null;
            return data;
          })
          .catch((message) => {
            this.resource = null;
            this.errorMsg = message;
          })
          .finally(() => {
            this.isLoading = false;
          });
    },

    btnAddMaterialToResource() {

      this.$refs.materialSelector.showPromise().then((material) => {

        return this.$store.dispatch('materials/attachResource', {
          materialId: material.id,
          resourceId: this.id
        }).then(({resource}) => {
          this.resource = resource;
        }).catch((error) => {
          alert(error);
        });
      }).catch(() => {
      });

    },

    btnDetachMaterialFromResource(material) {

      this.$store.dispatch('materials/detachResource',
          {materialId: material.id, resourceId: this.id}
      ).then(({material, resource}) => {
        this.resource = resource;

        if (material.resources && material.resources.length === 0) {
          this.$refs.myDialog.show({
            title: 'Rückfrage',
            content: 'Das eben entfernte Material ist jetzt keiner weiteren Ressource mehr zugeordet<br/>Soll ' + (material.title ? '"' + material.title + '"' : 'es') + ' <b>jetzt komplett</b> gelöscht werden?',
            yesText: 'Ja, löschen',
            yesVariant: 'success',
            noText: 'Nein, so lassen',
            noVariant: 'warning',
            allowBackdrop: false
          }).then((answerPositive) => {

            if (answerPositive === true) {
              this.$refs.myDialog.show({
                title: 'Lösche Material',
                content: 'Lösche ' + (material.title ? '"' + material.title + '"' : 'Material') + '...',
                yesEnabled: false,
                noEnabled: false,
                allowBackdrop: false
              }).catch(() => {
              });

              this.$store.dispatch('materials/deleteMaterial', material.id)
                  .then(() => {
                    this.$refs.myDialog.show({
                      title: 'Material gelöscht',
                      content: 'Material erfolgreich gelöscht!',
                      yesText: 'ok',
                      yesVariant: 'primary',
                      yesEnabled: true,
                      noEnabled: false,
                      allowBackdrop: true,
                    });
                  });
            }

          }).catch(({message}) => {
            this.$refs.myDialog.show({
              title: 'Warnung',
              content: message,
              yesText: 'ok',
              yesVariant: 'primary',
              yesEnabled: true,
              noEnabled: false,
              allowBackdrop: true,
            });
          });
        }

      });
    },

    btnCreateAutoMaterialFromResource() {

      this.$store.dispatch('resources/autoCreateMaterial', {resourceIds: [this.id]})
          .then((material) => {

            this.$router.push({
              name: 'material-detail',
              params: {
                id: material.id
              }
            });

          });

    },

    btnCreateAndAssignMaterialManually() {
      this.$refs.materialCreator.showPromise()
          .then((material) => {
            return this.$store.dispatch('materials/attachResource', {
              materialId: material.id,
              resourceId: this.id,
            })
          })
          .then(({material, resource}) => {
            this.forceReload++;
          });
    },

    btnCopyMaterial(material) {
      this.$store.dispatch('materials/copyMaterial', material.id)
          .then((material) => {
            this.forceReload++;
          });
    },

    updateRemotePath(value) {
      this._updateResource({
        remote_path: value
      }, this.$t('pool.Web-URL'));
    },

    updateNotes(value) {
      this._updateResource({
        notes: value
      }, this.$t('pool.Notes'));
    },

    updateIsPublic(value) {
      this._updateResource({
        is_public: value
      }, this.$t('pool.Publicity'));
    },

    _updateResource(data, flashLabel) {

      const startDialog = this.flashStartSaving(flashLabel);

      this.$store.dispatch('resources/update', {id: this.resource.id, data})
          .then((resource) => {
            this.forceReload++;
            this.flashSaved(flashLabel);
          })
          .catch((data) => {
            this.forceReload++;
            this.flashError(flashLabel, data)
          })
          .then(() => {
            startDialog.destroy();
          });

    },

    onResourceUpdated(resource) {
      this.forceReload++;
    }


  },

  components: {
    Edditable,
    Toggle,
    MaterialCreator,
    CustomDialog,
    MaterialSelector,
    resourceDetail,
    BTabs,
    BTab,
    BListGroup,
    BListGroupItem,
    pdfLimitation, audioLimitation, videoLimitation,
    user,
    BAlert
  }
}
</script>

<style scoped lang="scss">

.resource.container {
  .card {
    .materialData {


      .limitation {
        font-size: smaller;
        color: grey;
      }
    }
  }
}


.materialNavi {

}

.icon {
  position: relative;
  display: inline-block;
  background-size: contain;
  background-position: 0 0;
  height: 1rem;
  background-repeat: no-repeat;
  top: 0.1rem;
  width: 1rem;
}

.autocreation {
  background-image: url(/img/icons/entypo-plus/rocket.svg);
}

.manualcreation {
  background-image: url(/img/icons/entypo-plus/new-message.svg);
}

.assign {
  background-image: url(/img/icons/entypo-plus/flow-tree.svg);
}

</style>
