<template>
  <b-modal size="lg"
           :title="$t('pool.Select-a-resource')"
           lazy
           ref="myModal"
           @hide="_cancelPromise"
  >
    <template #modal-footer>
      <button type="button" class="btn btn-danger btn-sm" @click="hide">{{ $t('pool.Cancel') }}</button>
    </template>

    <b-form @submit.stop.prevent="_onSubmit" @reset="_onReset">

      <b-form-group horizontal
                    breakpoint="md"
                    label-cols="3"
                    label-cols-md="3"
                    label-cols-lg="1"
                    :label="$t('pool.ID')"
                    label-for="resourceid"
      >
        <b-form-input id="resourceid"
                      type="text"
                      v-model="form.id"
                      required
                      :placeholder="$t('pool.Resource-ID')">
        </b-form-input>
      </b-form-group>

      <b-form-group horizontal
                    breakpoint="md"
                    label-cols="3"
                    label-cols-md="3"
                    label-cols-lg="1"
                    :label="$t('pool.Order')"
                    label-for="order_by"
      >
        <b-form-select id="order_by"
                       name="order_by"
                       class="col-6"
                       v-model="form.order_by"
                       :options="[
                               	{value: 'updated_at', text: $t('pool.Updated-at')},
                               	{value: 'created_at', text: $t('pool.Created-at')},
                               	{value: 'id', text: $t('pool.ID')}
                               ]">
        </b-form-select>
        <span class="mx-1 px-1"/>
        <b-form-select id="order_dir"
                       name="order_dir"
                       class="col-5"
                       v-model="form.order_dir"
                       :options="[
                               	{value: 'asc', text: $t('pool.Ascending')},
                               	{value: 'desc', text: $t('pool.Descending')},
                               ]">
        </b-form-select>
      </b-form-group>

      <b-form-group
          v-if="excludedResourceId.length > 0"
          horizontal
          breakpoint="md"
          label-cols="3"
          label-cols-md="3"
          label-cols-lg="1"
          label-for="resourceid"
      >
        {{ $t('pool.Ignoring-resource-ids-xy', {xy: excludedResourceId.join(', ')}) }}
      </b-form-group>

    </b-form>

    <div class="resultList">
      <hr>

      <ul v-if="!searchOngoing">
        <li v-for="res in resourceSuggestions"
            class="resource"
            @click="_selectAndReturnResource(res)">
          ({{ $t('pool.ID') }}: {{ res.id }}) {{ res.notes }}
        </li>
      </ul>
      <materialpool-spinner v-if="searchOngoing"/>

      <b-alert fade
               :show="!searchOngoing && searchErrorMessage !== ''"
               variant="danger">
        {{ searchErrorMessage }}
      </b-alert>

    </div>

  </b-modal>
</template>

<script>

import {BAlert, BButton, BForm, BFormCheckbox, BFormGroup, BFormInput, BFormSelect, BModal} from '@/adapters/bootstrap';
import _debounce                                                                            from 'lodash/debounce';
import MaterialpoolSpinner
                                                                                            from "../../spinner/materialpool-spinner";
import {useResourcesStore}                                                                  from '../../../apps/main/stores/resources';

export default {
  name: "resourceSelector",

  props: {
    excludedResourceId: {
      type: Array,
      required: false,
      default() {
        return [];
      }
    }
  },

  data() {
    return {
      form: {
        id: '',
        recent: true,
        order_by: 'updated_at',
        order_dir: 'desc',
      },
      reject: null,
      resolve: null,

      resourceSuggestions: [],
      searchErrorMessage: '',
      searchOngoing: false,
    };
  },

  watch: {
    form: {
      handler(newValue, oldValue) {
        this.debounceUpdateMaterialSuggestions();
      },
      deep: true,
      immediate: true
    },
  },

  methods: {

    showPromise(returnFocusTo = null) {

      return new Promise((resolve, reject) => {
        this.resolve = resolve;
        this.reject  = reject;

        this.$refs.myModal.show(returnFocusTo);
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
      this._updateResourceSuggestions();
    }, 300),

    _updateResourceSuggestions() {

      this.searchErrorMessage = '';
      this.searchOngoing      = true;

      useResourcesStore().find(this.findResourceQuery)
          .then(({data}) => {
            return data;
          })
          .then(this._resourceSearchPositive)
          .catch(this._resourceSearchNegative);
    },

    _resourceSearchPositive(resources) {
      this.searchOngoing       = false;
      this.searchErrorMessage  = '';
      this.resourceSuggestions = resources;
    },

    _resourceSearchNegative(errorMessage) {
      this.searchOngoing       = false;
      this.searchErrorMessage  = errorMessage;
      this.resourceSuggestions = [];
    },

    _selectAndReturnResource(resource) {
      if (typeof this.resolve === 'function') {
        this.resolve(resource);
        this.$refs.myModal.hide();
        // this.resolve = null; // already done during hide()
        // this.reject  = null; // already done during hide()
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


  computed: {

    findResourceQuery() {
      let search = {
        order_by: this.form.order_by,
        order_dir: this.form.order_dir
      };

      if (this.form.id) {
        search.id = this.form.id;
      }

      if (this.excludedResourceId.length > 0) {
        search.ignore_ids = this.excludedResourceId;
      }

      return search;
    }
  },

  components: {
    MaterialpoolSpinner,
    BForm,
    BFormGroup,
    BFormInput,
    BFormSelect,
    BModal,
    BButton,
    BAlert,
    BFormCheckbox
  }


}
</script>

<style scoped lang="scss">
ul {
  padding-left: 0;
}

.resource {
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
import {useResourcesStore}                                                                  from '../../../apps/main/stores/resources';
