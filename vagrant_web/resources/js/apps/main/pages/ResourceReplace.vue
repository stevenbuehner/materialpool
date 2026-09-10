<template>
  <div class="container-fluid resourceReplace">
    <div class="row">
      <div class="col col-5 ">
        <b-form-group
            :invalid-feedback="error1"
            :state="state1"
            :description="$t('pool.replace-this-resource')"
        >

          <b-form-input
              :placeholder="$t('pool.insert-ID-here')"
              v-model="id1"
              :required="true"
              :invalid-feedback="error1"
              :state="state1"
              debounce="500"
              type="number"/>
        </b-form-group>

        <h4 class="pt-3" v-if="resource1">{{ $t('pool.Preview') }} {{ resource1.id }}</h4>
        <resource-preview :resource="resource1" :show-download="false" :show-open="true" v-if="resource1"/>

        <b-alert v-if="error1 !== ''" variant="warning">
          {{ error1 }}
        </b-alert>
      </div>

      <div class="col-1">
        <arrow-left-icon class="p-2"/>
        <b-button
            class="p-2"
            :variant="replaceMayStart ? 'success' : 'secondary'"
            :disabled="!replaceMayStart"
            @click="btnReplaceNow">
          {{ $t('pool.replace-resource-now') }}
        </b-button>
      </div>

      <div class="col col-5">
        <b-form-group
            :invalid-feedback="error2"
            :state="state2"
            :description="$t('pool.with-this-resource')"
        >

          <b-form-input
              :placeholder="$t('pool.insert-ID-here')"
              v-model="id2"
              :required="true"
              :autofocus="true"
              :invalid-feedback="error2"
              :state="state2"
              debounce="500"
              type="number"/>
        </b-form-group>

        <h4 class="pt-3" v-if="resource2">{{ $t('pool.Preview') }} {{ resource2.id }}</h4>
        <resource-preview :resource="resource2" :show-download="false" :show-open="true" v-if="resource2"/>

        <b-alert v-if="error2 !== ''" variant="warning">
          {{ error2 }}
        </b-alert>
      </div>
    </div>

  </div>
</template>

<script>
import {BAlert, BButton, BFormGroup, BFormInput} from '@/adapters/bootstrap';
import ResourcePreview                           from "../../../components/resource/show/resource-preview";
import ArrowLeftIcon                             from 'svg-icon/dist/svg/mfglabs/arrow_left.svg'
import {savingDialogs}                           from "../../../helper/flashMessages";

export default {
  name: "ResourceReplace",

  mixins: [savingDialogs],

  props: {
    r1: {
      type: Number,
      required: false,
      default: null
    },
    r2: {
      type: Number,
      required: false,
      default: null
    },
  },

  data() {
    return {
      id1: null,
      id2: null,
      error1: '',
      error2: '',
    }
  },

  watch: {
    r1: {
      handler(newValue) {
        this.id1 = newValue;
      },
      immediate: true,
    },
    r2: {
      handler(newValue) {
        this.id2 = newValue;
      },
      immediate: true,
    }
  },


  asyncComputed: {
    resource1() {
      if (this.id1 === null || this.id1 === '') {
        this.error1 = null;
        return null;
      }

      return this.$store.dispatch('resources/get', this.id1)
                 .then((resource) => {
                   this.error1 = '';
                   return resource;
                 })
                 .catch((message) => {
                   this.error1 = message;
                 });
    },
    resource2() {
      if (this.id2 === null || this.id2 === '') {
        this.error2 = null;
        return null;
      }

      return this.$store.dispatch('resources/get', this.id2)
                 .then((resource) => {
                   this.error2 = '';
                   return resource;
                 })
                 .catch((message) => {
                   this.error2 = message;
                 });
    }
  },

  computed: {
    state1() {
      if (this.id1 && this.id1 > 0 && this.error1 === '') {
        return true;
      } else {
        return false
      }
    },
    state2() {
      if (this.id2 && this.id2 > 0 && this.error2 === '') {
        return true;
      } else {
        return false
      }
    },

    replaceMayStart() {
      return this.state2 === true && this.state1 === true && this.id1 !== this.id2;
    }
  },

  methods: {
    btnReplaceNow() {

      const info = this.flashActionStartedWaiting(this.$t('pool.start-replacing-resource'));

      this.$store.dispatch('resources/replaceResource', {
        oldResourceId: this.id1,
        newResourceId: this.id2,
      })
          .then((resource) => {
            this.$router.push({
              name: this.$route.name,
              params: {
                r1: resource.id,
                r2: resource.id
              }
            });
            this.flashActionSuccessfullyFinished(this.$t('pool.resource-successfully-replaced'), info);
          })
          .catch((message) => {
            this.flashActionFailed(message, info);
          })
    }
  },

  components: {
    ResourcePreview,
    BAlert, BButton, BFormInput, BFormGroup,
    ArrowLeftIcon
  }


}
</script>

<style lang="scss">
.resourceReplace {
}
</style>