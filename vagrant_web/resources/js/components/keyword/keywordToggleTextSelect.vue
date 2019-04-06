<template>
    <div class="sbKeywordToggleTextSelect"
         :class="[{disabled, notDisabled : !disabled}, {editModeActive, editModeInactive: !editModeActive}, {isEmpty:!keyword}]"
         @click="toggleEditModeClick">

        <span v-if="!editModeActive && keyword" class="readMode" :title="title">{{keyword.title}}</span>

        <span v-else-if="!editModeActive && !keyword" class="readMode emptyTexts"
              :title="title">{{emptyPlaceholder}}</span>

        <vue-select
                v-else
                ref="mySelect"
                class="searchInput"
                :options="options"
                @search="onSearch"
                language="de-DE"
                label="title"
                :placeholder="searchPlaceholder"
                v-model="selection"
                @input="onChange"
                @search:blur="onBlur"
                @search:focus="onFocus"
        >

            <template slot="no-options">
                {{$t('pool.Nothing-found')}}
            </template>

            <template slot="option" slot-scope="option">
                <div class="d-center">
                    <span class="icon" :style="{backgroundImage: 'url(' + option.icon + ')'}"></span>
                    {{ option.title }}
                    <span v-if="option.new" class="badge badge-secondary">{{$t('pool.new')}}</span>
                </div>
            </template>

            <template slot="selected-option" slot-scope="option">
                <div class="selected d-center">
                    <span class="icon" :style="{backgroundImage: 'url(' + option.icon + ')'}"></span>
                    {{ option.title }}
                </div>
            </template>
        </vue-select>
    </div>
</template>

<script>

    import VueSelect from 'vue-select';
    import _debounce from 'lodash/debounce';

    let myTimeout = null;

    export default {

        name: "keywordToggleTextSelect",

        props: {
            keyword: {
                required: false,
                default: null
            },

            filterType: {
                type: String,
                required: false,
                default: 'person'
            },

            disabled: {
                type: Boolean,
                required: false,
                default: false
            },

            searchPlaceholder: {
                type: String,
                default() {
                    return this.$t('pool.Enter-name-please');
                },
            },

            emptyPlaceholder: {
                type: String,
                default() {
                    return this.$t('pool.No-author-given')
                }
            },

            title: {
                type: String,
                default() {
                    return this.$t('pool.Click-here-to-edit')
                }
            }
        },

        data() {
            return {
                editModeActive: false,

                options: [],
                selection: this.keyword,
            }
        },

        methods: {
            toggleEditModeClick() {
                if (this.editModeActive === false) {
                    this.editModeActive = true;

                    this.$nextTick((test) => {
                        try {
                            this.$refs.mySelect.$refs.search.focus();
                        } catch (e) {
                        }
                    })
                }
            },

            onSearch(search, loading) {
                const type = this.filterType || false;

                loading(true);
                this.search(loading, search, type, this);
            },

            search: _debounce((loading, search, type, vm) => {

                vm.$store.dispatch('keywords/search', {searchText: search, type})
                    .then((keywords) => {
                        keywords.push({
                            title: search,
                            type: type,
                            new: true
                        });
                        vm.options = keywords;
                        loading(false);
                    });

            }, 250),

            onFocus() {
                clearTimeout(myTimeout);
            },

            onBlur() {

                // Verstecke die Select-Box nach 4 Sekunden automatisch wieder.
                // Bzw. lass sie noch 4 Sekunden sichtbar, so dass der Author auch "entfernt" / "x" werden kann
                myTimeout = setTimeout(() => {
                    // Warten bis input => onChange gefeuert wurde ...
                    // this.$nextTick(() => {
                    this.editModeActive = false;
                    // })
                }, 4000);

            },

            onChange(input) {

                if ((input === null && this.keyword === null) || (input && this.keyword && input.id === this.keyword.id)) {
                    return;
                }

                if (input instanceof Object && input.id || input === null) {
                    // is valid keyword
                    this.emitNewKeywordSelection(input);
                    this.editModeActive = false;
                } else if (input && input.new === true) {
                    // Keyword first has to be created
                    this.$store.dispatch('keywords/create', {
                        title: input.title,
                        type: this.filterType
                    }).then((keyword) => {
                        this.selection = keyword;
                        this.emitNewKeywordSelection(keyword);
                    }).catch((errorMessage) => {
                        alert(errorMessage);
                    });
                    this.editModeActive = false;
                }
            },

            emitNewKeywordSelection(keyword) {
                this.$emit('newKeywordSelection', keyword);
            }
        },

        components: {
            VueSelect,
        },

        beforeDestroy() {
            clearTimeout(myTimeout);
        }


    }
</script>

<style type="scss">
    @import "../../../sass/theme";


    .sbKeywordToggleTextSelect {
        display: inline-block;
        padding: 0 .5em 0 0;
        border-radius: .2rem;

        &.editModeActive {
            width: 20em;
        }

        &.notDisabled.editModeInactive {
            cursor: pointer;

            &.isEmpty {
                color: rgba(60, 60, 60, .5);
            }

            &:hover {
                background-color: $tag-background-colour;
                color: $tag-font-colour;
                padding-left: .25em;
                padding-right: .25em;
            }
        }

        .searchInput {
            flex-grow: 1;
        }
    }

    .form-group {
        .sbKeywordToggleTextSelect {
            &.editModeActive {
                width: 100%;
            }
        }
    }
</style>