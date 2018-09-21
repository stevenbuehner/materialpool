<template>
    <b-modal size="lg"
             :title="headline || $t('pool.Create-a-material')"
             lazy
             ref="myModal"
             @hide="cancelPromise"
    >
        <template slot="modal-footer">
            <button type="button" class="btn btn-danger btn-sm" @click="hide">{{$t('pool.Cancel')}}</button>
            <button type="button" class="btn btn-primary btn-sm" @click="onReset">{{$t('pool.Reset')}}</button>
            <button type="button" class="btn btn-success btn-sm" @click="createAndReturnMaterial">{{$t('pool.Save')}}
            </button>
        </template>

        <b-form @submit.stop.prevent="onSubmit" @reset="onReset">
            <b-form-group horizontal
                          breakpoint="md"
                          :label="$t('pool.Title')"
                          label-for="materialtitle"
                          :label-cols="labelCols"
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
                          :label="$t('pool.Description')"
                          label-for="materialdescription"
                          :label-cols="labelCols"
            >
                <b-form-input id="materialdescription"
                              type="text"
                              v-model="form.description"
                              required
                              :placeholder="$t('pool.Add-description-here')">
                </b-form-input>
            </b-form-group>


            <b-form-group horizontal
                          breakpoint="md"
                          :label="$t('pool.Author')"
                          label-for="materialauthor"
                          :label-cols="labelCols"
            >
                <b-form-input id="materialauthor"
                              type="text"
                              v-model="form.author"
                              required
                              :placeholder="$t('pool.Name-of-material-author')">
                </b-form-input>
            </b-form-group>

            <div class="row">
                <div class="col-6">
                    <keyword-input v-model="keywordInput"></keyword-input>
                </div>
                <div class="col-6">
                    <bibleverse-input v-model="bibleverseInput"></bibleverse-input>
                </div>
            </div>


        </b-form>

    </b-modal>
</template>

<script>

    import bForm from 'bootstrap-vue/src/components/form/form';
    import bFormGroup from 'bootstrap-vue/src/components/form-group/form-group';
    import bFormInput from 'bootstrap-vue/src/components/form-input/form-input';
    import bModal from 'bootstrap-vue/src/components/modal/modal';
    import bButton from 'bootstrap-vue/src/components/button/button';
    import starRating from 'vue-star-rating';
    import KeywordInput from "../../keyword/keywordInput.vue";
    import BibleverseInput from "../../bibleverse/bibleverseInput";

    export default {
        name: "materialCreator",

        data() {
            return {
                form: {
                    title: this.title,
                    description: this.description,
                    rating: this.rating,
                    from_bot: false,
                    author: this.author,
                    keywords: this.keywordIds,
                    bibleverses: this.bibleverseIds
                },

                keywordInput: [],
                bibleverseInput: [],

                labelCols: 2,

                reject: null,
                resolve: null,

                searchErrorMessage: '',
            };
        },

        props: {
            headline: {
                type: String,
                false: true,
                default: ''
            },
            title: {
                type: String,
                required: false,
                default: ''
            },
            description: {
                type: String,
                required: false,
                default: ''
            },
            author: {
                type: String,
                required: false,
                default: ''
            },
            rating: {
                type: Number,
                required: false,
                default: 10
            },
            keywordIds: {
                type: Array,
                required: false,
                default() {
                    return [22, 46, 66];
                }
            },
            bibleverseIds: {
                type: Array,
                required: false,
                default() {
                    return [213];
                }
            }

        },

        created() {

            this.$store.dispatch('keywords/getMultiple', this.keywordIds)
                .then((keywords) => {
                    this.keywordInput = keywords;
                });

            this.$store.dispatch('bibleverses/getMultiple', this.bibleverseIds)
                .then((bibleverse) => {
                    this.bibleverseInput = bibleverse;
                });

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

            createAndReturnMaterial() {


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
                this.createAndReturnMaterial();
            },

            onReset() {

            }
        },

        components: {
            BibleverseInput,
            KeywordInput,
            bForm,
            bFormGroup,
            bFormInput,
            bModal,
            bButton,
            starRating
        }


    }
</script>

<style scoped>
    .starRatingText {
        font-size: smaller;
    }
</style>