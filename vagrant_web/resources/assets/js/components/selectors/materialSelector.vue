<template>
    <b-modal size="lg"
             :title="$t('pool.Select-a-material')"
             lazy
             ref="myModal"
             @hide="cancelPromise"
    >
        <template slot="modal-footer">
            <button type="button" class="btn btn-danger btn-sm" @click="hide">{{$t('pool.Cancel')}}</button>
        </template>

        <b-form @submit.stop.prevent="onSubmit" @reset="onReset">
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
            <ul v-if="!searchOngoing">
                <li v-for="mat in materialSuggestions"
                    @click="selectAndReturnMaterial(mat)">
                    ({{$t('pool.ID')}}: {{mat.id}}) {{mat.title}}
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
    import _ from 'lodash';

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

        props: {},

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

            cancelPromise() {

                if (typeof this.reject === 'function') {
                    this.reject('closed early');
                    // this.$refs.myModal.close();
                    this.resolve = null;
                    this.reject  = null;
                }

            },

            debounceUpdateMaterialSuggestions: _.debounce(function () {
                this.updateMaterialSuggestions();
            }, 300),

            updateMaterialSuggestions() {

                this.searchErrorMessage = '';

                if (this.form.id) {
                    this.searchOngoing = true;
                    this.$store.dispatch('materials/getMaterial', this.form.id)
                        .then((mat) => {
                            return [mat];
                        })
                        .then(this.materialSearchPositive)
                        .catch(this.materialSearchNegative);
                } else if (this.form.title) {
                    this.searchOngoing = true;
                    this.$store.dispatch('search/materialsWithParams', {
                        material: {
                            title: this.form.title
                        }
                    })
                        .then((result) => result.materials)
                        .then(this.materialSearchPositive)
                        .catch(this.materialSearchNegative);

                }
            },

            materialSearchPositive(materials) {
                this.searchOngoing       = false;
                this.searchErrorMessage  = '';
                this.materialSuggestions = materials;
            },

            materialSearchNegative(errorMessage) {
                this.searchOngoing       = false;
                this.searchErrorMessage  = errorMessage;
                this.materialSuggestions = [];
            },

            selectAndReturnMaterial(material) {
                if (typeof this.resolve === 'function') {
                    this.resolve(material);
                    this.$refs.myModal.hide();
                    // this.resolve = null; // already done during hide()
                    // this.reject  = null; // already done during hide()
                    this.$store.commit('recentmaterials/addRecentMaterialId', material.id);
                }
            },

            hide() {
                this.$refs.myModal.hide();
            },

            onSubmit() {
                this.$refs.myModal.hide();
            },
            onReset() {

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

<style scoped>

</style>