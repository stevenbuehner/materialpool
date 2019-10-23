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
                        :clearSearchOnSelect="false"
                        :close-on-select="false"
                        :selectOnTab="true"
                        :getOptionLabel="getTagLabelFromObject"
                        @input="onInputChanged"
                        @search="onSearch"
                >
                    <template v-slot:selected-option-container="{option, disabled, multiple, deselect}">
                        <span class="selected-tag" v-bind:key="option.id">

                            <div class="selected-relevance"
                                 :style="{width: option.pivot.relevance/300*100 + '%'}"></div>

                            <div class="text">
                                {{ getTagLabelFromObject(option) }}

                                <button :disabled="disabled" @click="deselect(option)"
                                        type="button"
                                        class="vs__deselect"
                                        aria-label="Remove option">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                        </span>
                    </template>

                    <template v-slot:option="option">
                        <span class="suggested-option" :class="{'is-new' : option.isNew}">
                            <span class="suggested-text">
                                {{ getTagLabelFromObject(option) }}
                            </span>
                            <span class="is-new badge badge-info" v-if="option.isNew">neu</span>
                        </span>
                    </template>
                </vue-select>
            </slot>

        </div>

    </div>
</template>

<script>
    import generalMixin from './generalSidebarFields.mixin';
    import VueSelect from 'vue-select';
    import tagIcon from 'svg-icon/dist/svg/material/style.svg';
    import {keywordTypes} from "../keyword/keywordDefaultIcons";
    import {debounce as _debounce, differenceBy as _differenceBy} from 'lodash';

    export default {
        name: "tagEdit",

        mixins: [generalMixin],

        props: {
            typefilter: {
                type: String,
                required: false,
                default: 'key',
                validator(value) {
                    return value === '' || keywordTypes.includes(value);
                }
            },

            value: {
                type: Array,
                required: true
            }

        },

        data() {
            return {
                suggestedTags: []
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

                const newObjects     = _differenceBy(currentValues, this.validTypesValues, (el) => el.id);
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
                        console.log(keywords);
                        vm.suggestedTags = keywords;
                        vm.suggestedTags.push({
                            title: search,
                            isNew: true,
                            id: 'new Keyword: ' + search
                        });
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
            tagIcon,
            VueSelect
        }
    }
</script>

<style type="scss">
    @import "generalCss";
    @import "~vue-select/src/scss/global/variables";
    @import "resources/sass/theme";

    .tagEditSidebarField {
        .editField {
            .selected-tag {
                display: flex;
                align-items: center;
                background-color: $sidebar-tag-background-color-active;
                border: $vs-selected-border-width $vs-selected-border-style $vs-selected-border-color;
                border-radius: $vs-border-radius;
                color: $sidebar-input-font-color-active;
                line-height: $vs-component-line-height;
                margin: 4px 2px 0px 2px;
                padding: 0 0.25em;

                position: relative;

                .selected-relevance {
                    position: absolute;
                    left: 0;
                    top: 0;
                    height: 100%;
                    background-color: $sidebar-tag-relevance-colour-active;
                }

                .text {
                    position: relative;
                }

            }

            .vs__dropdown-toggle {
                background: inherit;
                background-color: $sidebar-input-background-colour-active;
            }

            .vs--disabled {
                .selected-tag {
                    background-color: $sidebar-tag-background-color-disabled;
                    color: $sidebar-input-font-color-disabled;
                }

                .selected-relevance {
                    background-color: $sidebar-tag-relevance-colour-disabled;
                }

                .vs__dropdown-toggle {
                    background-color: $sidebar-input-background-colour-disabled;
                }

                .vs__search {
                    display: none;
                }

                .vs__actions {
                    display: none;
                }
            }
        }

        .suggested-option.is-new .suggested-text {
            text-decoration: underline;
            padding-right: .5em;
        }
    }

</style>