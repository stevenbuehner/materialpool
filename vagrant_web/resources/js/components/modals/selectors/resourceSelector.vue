<template>
    <b-modal size="lg"
             :title="$t('pool.Select-a-resource')"
             lazy
             ref="myModal"
             @hide="_cancelPromise"
    >
        <template slot="modal-footer">
            <button type="button" class="btn btn-danger btn-sm" @click="hide">{{$t('pool.Cancel')}}</button>
        </template>

        <b-form @submit.stop.prevent="_onSubmit" @reset="_onReset">

            <b-form-group horizontal
                          breakpoint="md"
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

        </b-form>

        <div class="resultList">
            <hr>

            <ul v-if="!searchOngoing">
                <li v-for="res in resourceSuggestions"
                    class="resource"
                    @click="_selectAndReturnResource(res)">
                    ({{$t('pool.ID')}}: {{res.id}}) {{res.notes}}
                </li>
            </ul>
            <hollow-dots-spinner v-if="searchOngoing"
                                 :dot-size="10"
                                 :dots-num="3"
                                 :animation-duration="1500"
                                 color="grey"></hollow-dots-spinner>

            <b-alert fade
                     :show="!searchOngoing && searchErrorMessage !== ''"
                     variant="danger">
                {{searchErrorMessage}}
            </b-alert>

        </div>

    </b-modal>
</template>

<script>

    import bForm from 'bootstrap-vue/src/components/form/form';
    import bFormGroup from 'bootstrap-vue/src/components/form-group/form-group';
    import bFormInput from 'bootstrap-vue/src/components/form-input/form-input';
    import bModal from 'bootstrap-vue/src/components/modal/modal';
    import bButton from 'bootstrap-vue/src/components/button/button';
    import {HollowDotsSpinner} from 'epic-spinners';
    import bAlert from 'bootstrap-vue/src/components/alert/alert';
    import _debounce from 'lodash/debounce';

    export default {
        name: "resourceSelector",

        data() {
            return {
                form: {
                    id: '',
                },
                reject: null,
                resolve: null,

                resourceSuggestions: [],
                searchErrorMessage: '',
                searchOngoing: false,
            };
        },

        props: {},

        watch: {
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
                this._updateResourceSuggestions();
            }, 300),

            _updateResourceSuggestions() {

                this.searchErrorMessage = '';

                if (this.form.id) {
                    this.searchOngoing = true;
                    this.$store.dispatch('resources/get', this.form.id)
                        .then((res) => {
                            return [res];
                        })
                        .then(this._resourceSearchPositive)
                        .catch(this._resourceSearchNegative);
                }
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

        components: {
            bForm,
            bFormGroup,
            bFormInput,
            bModal,
            bButton,
            HollowDotsSpinner,
            bAlert
        }


    }
</script>

<style scoped type="scss">
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