<template>
    <b-modal size="lg"
             :title="headline || $t('pool.Create-a-material')"
             lazy
             ref="myModal"
             @hide="_cancelPromise"
             @shown="_selectFocus"
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
                <button type="button" class="btn btn-secondary btn-sm" @click="_onReset" :disabled="buttonsDisabled">
                    {{$t('pool.Reset')}}
                </button>
                <button type="button" class="btn btn-success btn-sm" @click="_onSubmit" :disabled="buttonsDisabled">
                    {{$t('pool.Save')}}
                </button>
            </slot>

        </template>

        <b-form
                @submit.prevent="onSubmit"
                @reset="_onReset">
            <b-form-group horizontal
                          breakpoint="md"
                          :label="$t('pool.Title')"
                          label-for="materialtitle"
                          :label-cols="labelCols"
            >
                <b-form-input id="materialtitle"
                              type="text"
                              v-model.lazy="form.title"
                              required
                              :placeholder="$t('pool.Material-title')"
                              :disabled="formDisabled"
                              ref="titleInput"/>
            </b-form-group>


            <b-form-group horizontal
                          breakpoint="md"
                          :label="$t('pool.Description')"
                          label-for="materialdescription"
                          :label-cols="labelCols"
            >
                <b-form-input id="materialdescription"
                              type="text"
                              v-model.lazy="form.description"
                              required
                              :placeholder="$t('pool.Add-description-here')"
                              :disabled="formDisabled"/>
            </b-form-group>


            <b-form-group horizontal
                          breakpoint="md"
                          :label="$t('pool.Author')"
                          label-for="materialauthor"
                          :label-cols="labelCols"
            >

                <keyword-toggle-text-select
                        :keyword="form.author"
                        @newKeywordSelection="form.author = $event"
                        :disabled="formDisabled"
                        :emptyPlaceholder="$t('pool.Name-of-material-author')"/>

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
                        v-model="form.rating"
                        :read-only="formDisabled"/>
            </b-form-group>


            <div class="row">
                <div class="col-6">
                    <keyword-input
                            v-model="keywordInput"
                            @updated="updateKeywordForm"
                            :disabled="formDisabled"/>
                </div>
                <div class="col-6">
                    <bibleverse-input
                            v-model="bibleverseInput"
                            @updated="updateBibleverseForm"
                            :disabled="formDisabled"
                            :external-suggestions="externalBibleverseSuggestions"/>
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

	import {BForm}                 from 'bootstrap-vue';
	import {BAlert}                from 'bootstrap-vue';
	import {BFormGroup}            from 'bootstrap-vue';
	import {BFormInput}            from 'bootstrap-vue';
	import {BModal}                from 'bootstrap-vue';
	import {BButton}               from 'bootstrap-vue';
	import starRating              from 'vue-star-rating';
	import KeywordInput            from "../../keyword/keywordInput.vue";
	import BibleverseInput         from "../../bibleverse/bibleverseInput";
	import _debounce               from 'lodash/debounce';
	import KeywordToggleTextSelect from "../../keyword/keywordToggleTextSelect";
	import {RELEVANCE_USER_AVG}    from "../../../apps/config";

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
				default: -1
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
			},

			externalBibleverseSuggestions: {
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

		watch: {
			authorSearch: _debounce(function (searchValue) {
				this._getAuthorSuggestion(searchValue)
			}, 300)
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
				if (!this.form.title || this.form.title.trim().length < 3) {
					this.formErrors.push(this.$tc('pool.min-length', this.form.title.trim().length, {
						COUNT: this.form.title.trim().length,
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

				this.form.title       = (this.title !== '') ? this.title : this.$store.getters['materialcreator/getTitle'];
				this.form.description = (this.description !== '') ? this.description : this.$store.getters['materialcreator/getDescription'];
				this.form.rating      = (this.rating !== -1) ? this.rating : this.$store.getters['materialcreator/getRating'];
				this.form.from_bot    = (this.from_bot === false) ? false : this.$store.getters['materialcreator/getFromBot'];
				this.form.author      = (this.author !== '') ? this.author : this.$store.getters['materialcreator/getAuthor'];
				this.form.keywords    = [];
				this.form.bibleverses = [];


				// Wenn nur die IDs gegeben sind, dann nimm die Standard-Relevanz
				const kwIdsAndRelevance = this.keywordIds.length > 0 ? this.keywordIds.map((kw) => {
					return {id: kw.id, relevance: RELEVANCE_USER_AVG}
				}) : this.$store.getters['materialcreator/getKeywordIds'];

				// Wenn nur die IDs gegeben sind, dann nimm die Standard-Relevanz
				const bvIdsAndRelevance = (this.bibleverseIds.length > 0) ? this.bibleverseIds.map((bv) => {
					return {id: bv.id, relevance: RELEVANCE_USER_AVG}
				}) : this.$store.getters['materialcreator/getBibleverseIds'];


				// Lade den Author anhand der zwischengespeicherten ID nach
				if (this.form.author) {
					this.$store.dispatch('keywords/get', this.form.author)
					    .then((author) => {
						    this.form.author = author;
					    });
				}

				// Lade die Keywords anhand der zwischengespeicherten IDs nach und füge die Relevanz hinzu
				if (kwIdsAndRelevance.length > 0) {
					this.$store.dispatch('keywords/getMultiple', kwIdsAndRelevance.map(kw => kw.id))
					    .then((keywords) => {

						    for (let i in keywords) {
							    keywords[i].pivot = {relevance: kwIdsAndRelevance.find((el) => el.id === keywords[i].id).relevance}
						    }

						    this.keywordInput = keywords;
						    this.updateKeywordForm(keywords);
					    });
				}

				if (bvIdsAndRelevance.length > 0) {
					this.$store.dispatch('bibleverses/getMultiple', bvIdsAndRelevance.map(bv => bv.id))
					    .then((bibleverses) => {

						    for (let i in bibleverses) {
							    bibleverses[i].pivot = {relevance: bvIdsAndRelevance.find((el) => el.id === bibleverses[i].id).relevance}
						    }

						    this.bibleverseInput = bibleverses;
						    this.updateBibleverseForm(bibleverses);
					    });
				}

			},

			_selectFocus() {

				const el = this.$refs.titleInput.$el;

				el.focus();

				// Move cursor to selection end
				// see: https://css-tricks.com/snippets/javascript/move-cursor-to-end-of-input/
				if (typeof el.selectionStart == "number") {
					el.selectionStart = el.selectionEnd = el.value.length;
				} else if (typeof el.createTextRange != "undefined") {
					const range = el.createTextRange();
					range.collapse(false);
					range.select();
				}

			},

			useCurrentSelectionAsDefault() {

				this.$store.commit('materialcreator/setTitle', this.form.title);
				this.$store.commit('materialcreator/setDescription', this.form.description);
				this.$store.commit('materialcreator/setRating', this.form.rating);
				this.$store.commit('materialcreator/setFromBot', this.form.from_bot);
				this.$store.commit('materialcreator/setAuthor', (this.form.author) ? this.form.author.id : null);
				this.$store.commit('materialcreator/setKeywordIds', this.form.keywords);
				this.$store.commit('materialcreator/setBibleverseIds', this.form.bibleverses);

			},

		},

		components: {
			KeywordToggleTextSelect,
			BibleverseInput,
			KeywordInput,
			BForm,
			BAlert,
			BFormGroup,
			BFormInput,
			BModal,
			BButton,
			starRating
		}


	}
</script>

<style scoped>
    .starRatingText {
        font-size: smaller;
    }

</style>