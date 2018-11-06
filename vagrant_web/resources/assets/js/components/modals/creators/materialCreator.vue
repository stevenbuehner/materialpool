<template>
    <b-modal size="lg"
             :title="headline || $t('pool.Create-a-material')"
             lazy
             ref="myModal"
             @hide="cancelPromise"
    >
        <template slot="modal-footer">

            <slot name="all-buttons">
                <slot name="extra-buttons"></slot>
                <button type="button" class="btn btn-primary btn-sm"
                        @click="useCurrentSelectionAsDefault"
                        :disabled="buttonsDisabled">
                    {{$t('pool.use-this-as-template')}}
                </button>
                <button type="button" class="btn btn-secondary btn-sm" @click="hide" :disabled="buttonsDisabled">
                    {{$t('pool.Cancel')}}
                </button>
                <button type="button" class="btn btn-secondary btn-sm" @click="onReset" :disabled="buttonsDisabled">
                    {{$t('pool.Reset')}}
                </button>
                <button type="button" class="btn btn-success btn-sm" @click="onSubmit" :disabled="buttonsDisabled">
                    {{$t('pool.Save')}}
                </button>
            </slot>

        </template>

        <b-form @submit.prevent="onSubmit" @reset="onReset">
            <b-form-group horizontal
                          breakpoint="md"
                          :label="$t('pool.Title')"
                          label-for="materialtitle"
                          :label-cols="labelCols"
            >
                <b-form-input id="materialtitle"
                              type="text"
                              v-model.lazy.trim="form.title"
                              required
                              :placeholder="$t('pool.Material-title')"
                              :disabled="formDisabled">
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
                              v-model.lazy.trim="form.description"
                              required
                              :placeholder="$t('pool.Add-description-here')"
                              :disabled="formDisabled">
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
                              v-model.lazy.trim="form.author"
                              required
                              :placeholder="$t('pool.Name-of-material-author')"
                              :disabled="formDisabled">
                </b-form-input>
            </b-form-group>


            <b-form-group horizontal
                          breakpoint="md"
                          :label="$t('pool.Rating')"
                          :label-cols="labelCols"
            >
                <star-rating
                        :increment="1"
                        :max-rating="20"
                        inactive-color="lightgray"
                        active-color="black"
                        :star-size="15"
                        :inline="true"
                        text-class="starRatingText"
                        :rating="form.rating"
                        :read-only="formDisabled">
                </star-rating>
            </b-form-group>


            <div class="row">
                <div class="col-6">
                    <keyword-input
                            v-model="keywordInput"
                            @updated="updateKeywordForm"
                            :disabled="formDisabled"></keyword-input>
                </div>
                <div class="col-6">
                    <bibleverse-input
                            v-model="bibleverseInput"
                            @updated="updateBibleverseForm"
                            :disabled="formDisabled"></bibleverse-input>
                </div>
            </div>


        </b-form>


        <b-alert v-for="(alert, index) in formErrors"
                 :variant="'danger'"
                 class="mt-1 mb-1"
                 :show="3"
                 fade
                 dismissible
                 @dismissed="formErrors.splice(index,1)"
                 :key="alert">{{alert}}
        </b-alert>

    </b-modal>
</template>

<script>

    import bForm from 'bootstrap-vue/src/components/form/form';
    import bAlert from 'bootstrap-vue/src/components/alert/alert';
    import bFormGroup from 'bootstrap-vue/src/components/form-group/form-group';
    import bFormInput from 'bootstrap-vue/src/components/form-input/form-input';
    import bModal from 'bootstrap-vue/src/components/modal/modal';
    import bButton from 'bootstrap-vue/src/components/button/button';
    import starRating from 'vue-star-rating';
    import KeywordInput from "../../keyword/keywordInput.vue";
    import BibleverseInput from "../../bibleverse/bibleverseInput";


    let defaultForm = {
        title: '',
        description: '',
        rating: null,
        from_bot: false,
        author: '',
        keywords: [],
        bibleverses: [],
    };

    export default {
        name: "materialCreator",

        data() {
            return {
                form: {
                    title: '',
                    description: '',
                    rating: null,
                    from_bot: false,
                    author: '',
                    keywords: [],
                    bibleverses: [],
                },

                keywordInput: [],
                bibleverseInput: [],

                labelCols: 2,

                reject: null,
                resolve: null,

                formErrors: [],
                materialCreationRunning: false,
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
                    return [];
                }
            },
            bibleverseIds: {
                type: Array,
                required: false,
                default() {
                    return [];
                }
            }

        },

        computed: {
            formDisabled() {
                return this.materialCreationRunning;
            },

            buttonsDisabled() {
                return this.materialCreationRunning;
            }
        },

        created() {

            this._onReset();

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

            createAndReturnMaterial() {

                if (typeof this.resolve === 'function') {

                    this.materialCreationRunning = true;

                    this.$store.dispatch('materials/create', this.form)
                        .then((material) => {
                            console.info('Created successfull: ', material);

                            this.resolve(material);
                            this.resolve = null; // already done during hide()
                            this.reject  = null; // already done during hide()

                            this.$refs.myModal.hide();

                            this.$store.commit('recentmaterials/addRecentMaterialId', material.id);
                        })
                        .catch((response) => {
                            console.error(response);
                        })
                        .then(() => {
                            // Always
                            this.materialCreationRunning = false;
                        });
                }
            },

            updateBibleverseForm(bibleverses) {
                this.form.bibleverses = bibleverses.map((bv) => {
                    let response = {
                        from: bv.from,
                        to: bv.to,
                        id: bv.id,
                    };

                    if (bv.pivot && bv.pivot.relevance) {
                        response.relevance = bv.pivot.relevance;
                    }

                    return response;
                });
            },

            updateKeywordForm(keywords) {
                this.form.keywords = keywords.map((kw) => {
                    let response = {
                        type: kw.type,
                        title: kw.title,
                        id: kw.id,
                    };

                    if (kw.pivot && kw.pivot.relevance) {
                        response.relevance = kw.pivot.relevance;
                    }

                    return response;
                });
            },

            hide() {
                this.$refs.myModal.hide();
            },

            _onSubmit() {

                this.checkRequirements();

                if (this.formErrors.length === 0) {
                    this.createAndReturnMaterial();
                }

            },

            checkRequirements() {

                this.formErrors = [];

                // Check title
                if (!this.form.title || this.form.title.length < 3) {
                    this.formErrors.push(this.$tc('pool.min-length', this.form.title.length, {
                        COUNT: this.form.title.length,
                        REQUIRED: 3,
                        FIELD: this.$t('pool.Title')
                    }));
                }

                if (!this.form.rating) {
                    this.formErrors.push(this.$tc('pool.rating-missing'));
                }

                if (this.form.keywords.length < 3) {
                    this.formErrors.push(this.$tc('pool.min-3-keywords'));
                }

            },

            _onReset() {
                this.formErrors = [];

                this.form.title       = defaultForm.title || this.title;
                this.form.description = defaultForm.description || this.description;
                this.form.rating      = defaultForm.rating || this.rating;
                this.form.from_bot    = false;
                this.form.author      = defaultForm.author || this.author;
                this.form.keywords    = [];
                this.form.bibleverses = [];

                const kw = (defaultForm.bibleverses.length > 0) ? defaultForm.keywords.map(kw => kw.id) : this.keywordIds;
                this.$store.dispatch('keywords/getMultiple', kw)
                    .then((keywords) => {
                        this.keywordInput = keywords;
                        this.updateKeywordForm(keywords);
                    });

                const bv = (defaultForm.bibleverses.length > 0) ? defaultForm.bibleverses.map(bv => bv.id) : this.bibleverseIds;
                this.$store.dispatch('bibleverses/getMultiple', bv)
                    .then((bibleverses) => {
                        this.bibleverseInput = bibleverses;
                        this.updateBibleverseForm(bibleverses);
                    });
            },

            useCurrentSelectionAsDefault() {
                defaultForm = JSON.parse(JSON.stringify(this.form));
            },

        },

        components: {
            BibleverseInput,
            KeywordInput,
            bForm,
            bAlert,
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