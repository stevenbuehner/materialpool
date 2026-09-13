<template>
  <transition>
    <b-card
        class="mb-2"
        :border-variant="updateAvailable || installAvailable ? 'warning' : 'success'"
        v-if="bundle"
        no-body
    >
      <template #header>

        <div class="d-flex justify-content-between bundleProgressFront">
          <div>
            <h4 class="card-title">{{ bundle.name }}</h4>
            <h6 class="card-subtitle text-muted">{{ installedVersion }},
              {{ recentOrFormat(dayjs(bundle.updated_at)) }}</h6>
          </div>

          <div class="bundleTodoMenu">
            <b-button variant="warning"
                      v-if="updateAvailable && isRunning === false"
                      @click="btnStartUpdate">
              {{ $t('pool.please-run-update-for', {VERSION: info.version}) }}
            </b-button>
            <b-button variant="warning"
                      v-if="installAvailable && isRunning === false"
                      @click="btnStartInstallation">
              {{ $t('pool.install') }}
            </b-button>
            <b-button variant="danger"
                      v-if="bundle.is_installed && isRunning === false"
                      @click="btnUninstall">
              {{ $t('pool.uninstall') }}
            </b-button>
            <b-button variant="danger"
                      v-if="isRunning === true && cancelRequested === false"
                      @click="btnCancelProgress">
              {{ $t('pool.cancel') }}
            </b-button>
            <b-button variant="danger"
                      v-if="isRunning === true && cancelRequested === true"
                      disabled>{{ $t('pool.canceling-update') }}
            </b-button>
          </div>
        </div>

        <b-progress
            v-if="isRunning"
            :max="max"
            striped
            animated>
          <b-progress-bar :value="current" style="white-space: nowrap; overflow: visible;">
                        <span v-if="updateProgressPercentage >= 20">
                            <span v-if="this.max > 1">({{ current }} / {{ max }})</span> {{ updateProgressLabel }}
                        </span>
          </b-progress-bar>
          <span v-if="updateProgressPercentage <= 20" class="ms-2">
                        <span v-if="this.max > 1">({{ current }} / {{ max }})</span> {{ updateProgressLabel }}
                    </span>
        </b-progress>
      </template>

      <b-list-group flush>
        <b-list-group-item><span
            class="text-muted">{{ $tc('pool.Bundle') }} {{ bundle.name }} {{ $t('pool.by') }} {{ bundle.author }} </span>
          <br>
          {{ bundle.description }}
        </b-list-group-item>
        <b-list-group-item v-if="info">
          {{ $tc('pool.material-count', info.count_materials, {count: info.count_materials}) }},
          {{ $tc('pool.resource-count', info.count_files, {count: info.count_files}) }},
        </b-list-group-item>
        <b-list-group-item v-if="info">
          {{ $t('pool.export-date') }}: {{ recentOrFormat(dayjs(info.exportDate)) }}
        </b-list-group-item>
      </b-list-group>

    </b-card>
  </transition>
</template>

<script>
import {BButton, BCard, BListGroup, BListGroupItem, BProgress, BProgressBar} from '@/adapters/bootstrap'
import {formatLocalizedDate}                                                 from "../../helper/datetime.mixin";
import {useBundlesStore}                                                     from '../../apps/main/stores/bundles';


export default {
  name: "bundle",

  mixins: [formatLocalizedDate],

  props: {
    uuid: {
      type: String,
      required: true
    }
  },

  data() {
    return {

      isRunning: false,
      isInitializing: false,
      cancelRequested: false,
      current: 0,
      max: 0,

      forceBundleUpdate: false,

    };
  },

  computed: {
    installedVersion() {

      if (this.bundle) {
        if (!this.bundle.installed_version) {
          return this.$t('pool.not-installed');

        } else {
          return 'Version ' + this.bundle.installed_version;
        }
      }

      return null;
    },


    installAvailable() {
      return this.bundle && this.bundle.is_installed === false;

    },

    updateAvailable() {
      return this.bundle && this.bundle.is_installed === true && this.bundle.update_available === true;
    },

    updateProgressPercentage() {
      if (this.max === 0) {
        return 0;
      } else {
        return Math.floor(this.current / this.max * 100);
      }
    },

    updateProgressLabel() {

      if (this.isInitializing) {
        return this.$t('pool.update-is-initializing');
      }

      return this.updateProgressPercentage + '%';

    },
  },


  asyncComputed: {
    bundle: {
      get() {

        if (this.forceBundleUpdate === true) {
          console.log('FORCE reloading bundle');
          useBundlesStore().allBundles(this.forceBundleUpdate)
              .then(() => {
                console.log('FORCE reloaded bundle');
                this.forceBundleUpdate = false;
              });
        }

        return useBundlesStore().getBundle(this.uuid);
      },
      default: null,
      watch() {
        this.forceBundleUpdate
      }
    },
    info: {
      get() {
        return useBundlesStore().getBundleInfo(this.uuid);
      },
      default: null,
      watch() {
        this.forceBundleUpdate
      }
    },
  },

  methods: {
    btnStartUpdate() {

      if (this.isRunning === false) {
        this.isRunning      = true;
        this.max            = 100;
        this.current        = 100;
        this.isInitializing = true;

        useBundlesStore().initUpdateJobs(this.bundle.id)
            .then(({openJobs}) => {
                  this.isInitializing = false;
                  this.max            = openJobs;
                  // this.max            = (deleteJobs || 0) + (updateJobs || 0);
                  this.current        = 0;
                  this.runNextJobs();
                }
            )
            .catch(() => {
              this.isRunning = false;
              this.flashError('Error while initializing Install-Jobs!');
            });
      }


    },

    runNextJobs() {

      if (this.cancelRequested === true) {
        this.isRunning       = false;
        this.cancelRequested = false;
        return;
      }

      this.isRunning = true;

      return useBundlesStore().runJobs(this.bundle.id)
                 .then(({done, open}) => {
                   this.max     = parseInt(Math.max(this.current + open + done, this.max));
                   this.current = parseInt(this.max - open);

                   if (this.current >= this.max) {
                     this.isRunning = false;
                   } else {
                     this.runNextJobs();
                   }

                 }).catch(() => {
            this.isRunning = false;
            this.flashError('Error in job!');
          });

    },


    btnStartInstallation() {
      this.btnStartUpdate();
    },

    btnCancelProgress() {
      this.cancelRequested = true;
      this.$asyncComputed.bundle.update();
    },

    btnUninstall() {

      if (this.isRunning === false) {
        this.isRunning      = true;
        this.max            = 100;
        this.current        = 100;
        this.isInitializing = true;

        useBundlesStore().initUninstallJobs(this.bundle.id)
            .then(({openJobs}) => {
                  this.isInitializing = false;
                  this.max            = openJobs;
                  this.current        = 0;
                  this.runNextJobs();
                }
            )
            .catch(() => {
              this.isRunning = false;
              this.flashError('Error while initializing Unintsall-Jobs!');
            });
      }

    }
  },

  components: {
    BCard,
    BButton,
    BListGroup,
    BListGroupItem,
    BProgress,
    BProgressBar
  }
}
</script>

<style>
</style>
