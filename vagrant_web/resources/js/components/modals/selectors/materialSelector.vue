<template>
  <b-modal size="lg"
           :title="$t('pool.Select-a-material')"
           lazy
           ref="myModal"
           @hide="_cancelPromise"
  >
    <template #modal-footer>
      <button type="button" class="btn btn-danger btn-sm" @click="hide">{{ $t('pool.Cancel') }}</button>
    </template>

    <b-form @submit.stop.prevent="onSubmit" @reset="_onReset">
      <b-form-group horizontal
                    breakpoint="md"
                    :label="$t('pool.Title')"
                    label-for="materialtitle"
      >
        <b-form-input id="materialtitle"
                      type="text"
                      v-model="form.title"
                      required
                      :placeholder="$t('pool.Material-title')">
        </b-form-input>
      </b-form-group>

      <b-form-group horizontal
                    breakpoint="md"
                    :label="$t('pool.ID')"
                    label-for="materialid"
      >
        <b-form-input id="materialid"
                      type="text"
                      v-model="form.id"
                      required
                      :placeholder="$t('pool.Material-ID')">
        </b-form-input>
      </b-form-group>

    </b-form>

    <div class="resultList">
      <hr>

      <ul v-if="!searchOngoing">
        <li v-for="mat in materialSuggestions"
            class="material"
            @click="_selectAndReturnMaterial(mat)">
          ({{ $t('pool.ID') }}: {{ mat.id }}) {{ mat.title }}
        </li>
      </ul>
      <materialpool-spinner v-if="searchOngoing"/>

      <b-alert fade
               :show="!searchOngoing && searchErrorMessage !== ''"
               variant="danger">
        {{ searchErrorMessage }}
      </b-alert>

      <hr v-if="lastMaterials.length > 0 && materialSuggestions.length > 0">

      <div class="lastMaterials" v-if="lastMaterials.length > 0">
        <span class="labelLastMaterials">{{ $t('pool.last-used-materials') }}:</span>
        <ul>
          <li v-for="mat in lastMaterials"
              class="material"
              @click="_selectAndReturnMaterial(mat)">
            ({{ $t('pool.ID') }}: {{ mat.id }}) {{ mat.title }}
          </li>
        </ul>
      </div>
    </div>

  </b-modal>
</template>

<script>

import {BAlert, BButton, BForm, BFormGroup, BFormInput, BModal} from '@/adapters/bootstrap';
import _debounce                                                from 'lodash/debounce';
import MaterialpoolSpinner                                      from "../../spinner/materialpool-spinner";
import {useRecentMaterialsStore}                                from '../../../apps/main/stores/recentMaterials';
import {useMaterialsStore}                                      from '../../../apps/main/stores/materials';

export default {
  name: "materialSelector",

  data() {
    return {
      form: {
        title: '',
        id: '',
      },
      reject: null,
      resolve: null,

      materialSuggestions: [],
      searchErrorMessage: '',
      searchOngoing: false,
    };
  },

  props: {
    lastMaterials: {
      type: Array,
      required: false,
      default() {
        return [];
      }
    }
  },

  watch: {
    'form.title': function (newVal, oldVal) {
      this.debounceUpdateMaterialSuggestions();
    },
    'form.id': function () {
      this.debounceUpdateMaterialSuggestions();
    }
  },

  methods: {

    showPromise() {

      return new Promise((resolve, reject) => {
        this.resolve = resolve;
        this.reject  = reject;

        this.$refs.myModal.show();

      });

    },

    _cancelPromise() {

      if (typeof this.reject === 'function') {
        this.reject('closed early');
        // this.$refs.myModal.close();
        this.resolve = null;
        this.reject  = null;
      }

    },

    debounceUpdateMaterialSuggestions: _debounce(function () {
      this._updateMaterialSuggestions();
    }, 300),

    _updateMaterialSuggestions() {

      this.searchErrorMessage = '';

      if (this.form.id) {
        this.searchOngoing = true;
        useMaterialsStore().getMaterialById(this.form.id)
            .then((mat) => {
              return [mat];
            })
            .then(this._materialSearchPositive)
            .catch(this._materialearchNegative);
      } else if (this.form.title) {
        this.searchOngoing = true;
        this.$store.dispatch('search/materialsWithParams', {
          material: {
            title: this.form.title
          }
        })
            .then((result) => result.materials)
            .then(this._materialSearchPositive)
            .catch(this._materialearchNegative);

      }
    },

    _materialSearchPositive(materials) {
      this.searchOngoing       = false;
      this.searchErrorMessage  = '';
      this.materialSuggestions = materials;
    },

    _materialearchNegative(errorMessage) {
      this.searchOngoing       = false;
      this.searchErrorMessage  = errorMessage;
      this.materialSuggestions = [];
    },

    _selectAndReturnMaterial(material) {
      if (typeof this.resolve === 'function') {
        this.resolve(material);
        this.$refs.myModal.hide();
        // this.resolve = null; // already done during hide()
        // this.reject  = null; // already done during hide()
        useRecentMaterialsStore().addRecentMaterialId(material.id);
      }
    },

    hide() {
      this.$refs.myModal.hide();
    },

    _onSubmit() {
      this.$refs.myModal.hide();
    },
    _onReset() {

    }
  },

  components: {
    MaterialpoolSpinner,
    BForm,
    BFormGroup,
    BFormInput,
    BModal,
    BButton,
    BAlert
  }


}
</script>

<style scoped lang="scss">
.labelLastMaterials {
  font-weight: bold;
}

ul {
  padding-left: 0;
}

.material {
  padding: 0.5em;
  border: 1px solid white;
  list-style: none;
  cursor: pointer;

  &:hover {
    border: 1px solid grey;
    background-color: lightgrey;
  }
}

</style>
