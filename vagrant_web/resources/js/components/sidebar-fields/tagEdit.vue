<template>
    <div class="sideBarField tagEditSidebarField">

        <div class="label">
            <slot name="label">
                <slot name="icon">
                    <tag-icon/>
                </slot>

                <span class="title">
                    <slot name="title">{{name}}</slot>
                </span>
            </slot>
        </div>

        <div class="editField">

            <slot name="input">
                <vue-select
                        :placeholder="placeholder"
                        :disabled="disabled"
                        :value="validTypesValues"
                        :options="suggestedTags"
                        :filterBy="filterSuggestionsBy"
                        :filterable="true"
                        :multiple="true"
                        :clearSearchOnSelect="clearOnSelect"
                        :close-on-select="false"
                        :selectOnTab="true"
                        :getOptionLabel="getTagLabelFromObject"
                        @input="onInputChanged"
                        @search="onSearch"
                        @search:blur=""
                >
                    <template v-slot:selected-option-container="{option, disabled, multiple, deselect}">
                        <dragable-element
                                :id="option.id"
                                :label="getTagLabelFromObject(option)"
                                :disable-move-relevance="disabled"
                                :disable-remove-element="disabled"
                                :relevance="option.pivot.relevance"
                                @deselect="deselect(option)"
                                @request-update-relevance="$emit('request-update-relevance', {tag: option, relevance: $event});"
                        ></dragable-element>
                    </template>

                    <template v-slot:option="option">
                        <span class="suggested-option" :class="{'is-new' : option.isNew}">
                            <span class="tagOptionIcon">
                                <slot name="icon">
                                    <tag-icon/>
                                </slot>
                            </span>
                            <span class="suggested-text">
                                {{ getTagLabelFromObject(option) }}
                            </span>
                            <span class="is-new badge badge-info" v-if="option.isNew">{{$t('pool.new')}}</span>
                        </span>
                    </template>

                    <template v-slot:no-options>{{$t('pool.no-results')}}</template>

                </vue-select>
            </slot>

        </div>

    </div>
</template>

<script>
	import generalMixin                                           from './generalSidebarFields.mixin';
	import VueSelect                                              from 'vue-select/src/components/Select';
	import tagIcon                                                from 'svg-icon/dist/svg/material/style.svg';
	import {keywordTypes}                                         from "../keyword/keywordDefaultIcons";
	import {debounce as _debounce, differenceBy as _differenceBy} from 'lodash';
	import DragableElement                                        from "./vue-select/dragable-element";

	export default {
		name: "tagEdit",

		mixins: [generalMixin],

		props: {
			typefilter: {
				type: String,
				required: false,
				default: '',
				validator(value) {
					return value === '' || keywordTypes.includes(value);
				}
			},

			value: {
				required: true,
				validator(value) {

					return typeof value === 'object';

				}
			},


		},

		data() {
			return {
				suggestedTags: [],
				clearOnSelect: true,
			};
		},

		computed: {
			validTypesValues() {
				// About bind: https://stackoverflow.com/questions/49714015/why-does-this-inside-filter-gets-undefined-in-vuejs
				return this.value.filter(function (el) {
						return el.type === this.typefilter || this.typefilter === '';
					}.bind(this)
				);
			},

			invalidTypesValues() {
				return this.value.filter(function (el) {
						return el.type !== this.typefilter && this.typefilter !== '';
					}.bind(this)
				);
			}


		},

		methods: {
			onInputChanged(currentValues) {

				const newObjects = _differenceBy(currentValues, this.validTypesValues, (el) => el.id);
				const removedObjects = _differenceBy(this.validTypesValues, currentValues, (el) => el.id);

				// console.log(newObjects, removedObjects);

				for (let i in newObjects) {
					this.$emit('input:added', newObjects[i]);
				}

				for (let i in removedObjects) {
					this.$emit('input:removed', removedObjects[i]);
				}

				this.$emit('input', this.invalidTypesValues.concat(currentValues));

			},


			getTagLabelFromObject(value) {
				if (typeof value === 'object') {
					if (!value.hasOwnProperty('title')) {
						return console.warn(
							`[vue-select warn]: Label key "option.title" does not` +
							` exist in options object ${JSON.stringify(value)}.\n` +
							'http://sagalbot.github.io/vue-select/#ex-labels'
						)
					} else {
						return value.title;
					}

				} else {
					return value;
				}
			},

			onSearch(search, loading) {
				loading(true);

				this.suggestedTags = [];

				this.search(loading, search, this);
			},

			// _.debounce is a function provided by lodash to limit how
			// often a particularly expensive operation can be run.
			// To learn
			// more about the _.debounce function (and its cousin
			// _.throttle), visit: https://lodash.com/docs#debounce
			search: _debounce((loading, search, vm) => {

				vm.$store.dispatch('keywords/search', {
					searchText: search,
					type: vm.typefilter || false,
					per_page: 40
				})
				  .then((keywords) => {
					  // console.log(keywords);
					  vm.suggestedTags = keywords;

					  const newTag = {
						  title: search,
						  isNew: true,
						  id: 'new Keyword: ' + search,
					  };

					  if (vm.typefilter) {
						  newTag.type = vm.typefilter;
					  }

					  vm.suggestedTags.push(newTag);
				  })
				  .catch((err) => {
					  console.error(err);
				  })
				  .then(() => {
					  // Always
					  loading(false);
				  });

			}, 250),

			filterSuggestionsBy(object) {
				return this.value.find((el) => el.id === object.id) === undefined;
			}

		},

		components: {
			DragableElement,
			tagIcon,
			VueSelect
		}
	}
</script>

<style type="scss">
    @import "generalCss";
    @import "resources/sass/theme";

    .tagEditSidebarField {
        .editField {

            .vs__dropdown-toggle {
                background-color: $sidebar-input-background-colour-active;

                .vs__selected-options input {
                    min-width: 50%;

                    &::placeholder {
                        color: $sidebar-input-text-colour-placeholder;
                    }
                }
            }

            .vs--disabled {
                .selected-tag {
                    background-color: $sidebar-tag-background-color-disabled;
                    color: $sidebar-input-font-color-disabled;
                }

                .selected-relevance {
                    background-color: $sidebar-tag-relevance-colour-disabled;
                }

                .vs__search {
                    display: none;
                }

                .vs__actions {
                    display: none;
                }

                .vs__dropdown-toggle {
                    // background-color: $vs-state-disabled-bg;
                    cursor: not-allowed;
                }

            }

            .vs__dropdown-option {

                padding-left: .5em;

                .tagOptionIcon svg {
                    width: 1em;
                    height: 1em;
                }
            }
        }

        .suggested-option.is-new .suggested-text {
            text-decoration: underline;
            padding-right: .5em;
        }
    }

</style>