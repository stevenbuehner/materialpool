<template>
    <div class="kw-wrapper" :class="[size]">
        <div class="btn btn-sm btn-secondary sb-keyword"
             :class="{'tag-readonly' : !editable, 'tag-editable' : editable}"
             @click.right.prevent="$refs.menu.openMenu($event)"
             @mousedown.left.prevent="startDrag"
             @dblclick.prevent="openKeywordEditModal"
             role="button"
             :style="{color: theme.colors.color, backgroundColor: theme.colors.background}">
            <div v-if="hasPivot" class="sb-progress-bar" :style="styleObject"></div>
            <span class="icon" :style="{backgroundImage : 'url(' + myKeyword.icon + ')'}"></span>
            <span class="text">{{ myKeyword.title }}</span>
        </div>

        <b-modal ref="editKeyword" title="Edit Keyword" @ok="storeModalChanges">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-3">
                        <label for="keywordText">Label:</label>
                    </div>
                    <div class="col-sm-9">
                        <b-form-input
                                name="keywordText"
                                id="keywordText"
                                type="text"
                                v-model="modifiedKeywordData.title"
                                autofocus
                        ></b-form-input>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-sm-3">
                        <label for="selectType">Select Type:</label>
                    </div>
                    <div class="col-sm-9">
                        <b-form-select
                                name="keywordType"
                                id="keywordType"
                                type="text"
                                v-model="modifiedKeywordData.type"
                        >
                            <option value="key">Keyword</option>
                            <option value="person">Person</option>
                            <option value="place">Place</option>
                            <option value="lang">Language</option>
                        </b-form-select>
                    </div>
                </div>

                <div class="row mt-2">
                    <div class="col-sm-3">
                        <label for="selectIcon">Select Icon:</label>
                    </div>
                    <div class="col-sm-9">
                        Coming soon
                    </div>
                </div>
            </div>
        </b-modal>


        <context-menu ref="menu">
            <context-menu-item @click="goToKeywordSearch">nach '{{myKeyword.title}}' suchen</context-menu-item>
            <context-menu-item v-if="editable" @click="openKeywordEditModal">bearbeiten</context-menu-item>
            <context-menu-item v-if="removeable" @click="removeKeyword">entfernen</context-menu-item>
        </context-menu>

    </div>
</template>


<script>
    import bModal from 'bootstrap-vue/es/components/modal/modal';
    import bFormInput from 'bootstrap-vue/es/components/form-input/form-input';
    import bFormSelect from 'bootstrap-vue/es/components/form-select/form-select';
    import contextMenu from './../context-menu/context-menu.vue';
    import contextMenuItem from "../context-menu/context-menu-item.vue";
    import axios from 'axios';
    import {tagging} from './../theme';
    import {
        keywordSearchLink,
        keywordUpdatePivotRoute,
        keywordUpdateRoute,
        materialRemoveKeywordRoute
    } from './../serverRoutes';

    import {draggingSupport} from "./dragging.mixin";

    export default {

        mixins: [
            draggingSupport
        ],

        props: {
            // Passing in only. Later working with myKeyword (data)
            keyword: {
                type: Object,
                required: true
            },

            materialId: {
                type: Number,
                required: true
            },

            searchlink: {
                type: String,
                required: false,
                default: ''
            },
            size: {
                type: String,
                required: false,
                default: 'normal'
            },
            editable: {
                type: Boolean,
                required: false,
                default: true
            },
            removeable: {
                type: Boolean,
                required: false,
                default: false
            },
        },

        model: {
            prop: 'keyword',
            event: 'saved'
        },


        data: function () {
            return {
                menuIsOpen: false,
                modifiedKeywordData: {},
                myKeyword: {}
            };
        },

        computed: {

            theme() {
                return tagging;
            },

            searchLink() {
                return keywordSearchLink(this.myKeyword);
            },

            relevance() {
                if (this.dragging.ongoing === true) {
                    return this.dragDifference;
                } else {
                    return this.myKeyword.pivot.relevance;
                }
            },

            hasPivot() {
                return this.myKeyword.pivot !== undefined && this.myKeyword.pivot.relevance !== undefined;
            },


            styleObject: function () {
                return {
                    width: this.relevance / 300 * 100 + '%',
                    backgroundColor: this.dragging.ongoing === true ? this.theme.colors.progressbar.dragging : this.theme.colors.progressbar.default,
                }
            },

        },

        created: function () {

            // Needs to be copied. Because any changes in properties are not recognized in computed properties
            this.myKeyword = JSON.parse(JSON.stringify(this.keyword));

        },

        methods: {

            storeModalChanges() {

                // Ignore if nothing was changed
                if (this.myKeyword.title === this.modifiedKeywordData.title
                    && this.myKeyword.type == this.modifiedKeywordData.type) {
                    return;
                }

                this.updateKeywordData({
                    title: this.modifiedKeywordData.title,
                    type: this.modifiedKeywordData.type
                });
            },

            updateKeywordData(properties) {

                this.$emit('saving', properties);

                properties._method = 'PUT';

                return axios.post(keywordUpdateRoute(this.myKeyword.id), properties)
                    .then((response) => {


                            for (let i in response.data) {
                                this.myKeyword[i] = response.data[i]
                            }

                            this.emitSaved(this.myKeyword);
                            return response.data;

                        }
                    ).catch((response) => {

                        // on failure
                        this.$emit('savingError', {
                            tag: this.myKeyword, // "Tag" is used for bibleverses and keywords
                            msg: this.parseResponseErrors(response.response)
                        });
                    });

            },

            updatePivot(pivot) {
                this.$emit('savingPivot', {pivot: pivot});

                pivot._method = 'PUT';

                return axios.post(keywordUpdatePivotRoute(this.materialId, this.myKeyword.id), pivot)
                    .then((response) => {

                            this.myKeyword.pivot = response.data.pivot;

                            this.emitSaved(this.myKeyword);

                            return response.data;

                        }
                    ).catch((response) => {
                        // on failure
                        this.$emit('savingPivotError', {
                            tag: this.myKeyword,  // "tag" is used for bibleverses and keywords
                            msg: this.parseResponseErrors(response.response)
                        });
                    });
            },

            removeKeyword() {

                let params = {
                    _method: 'DELETE'
                };

                axios.post(materialRemoveKeywordRoute(this.materialId, this.myKeyword.id), params)
                    .then((response) => {
                            this.emitRemoved();
                        }
                    ).catch((response) => {
                    // on failure
                    console.error('Failed to remove keyword', this.myKeyword)
                });

            },

            emitSaved(newKeyword) {
                this.$emit('saved', newKeyword);
            },

            emitRemoved() {
                this.$emit('removed', this.myKeyword);
            },

            parseResponseErrors(response) {
                let msg = 'Error! ';

                if (response.data && response.data.errors) {
                    for (let i in response.data.errors) {
                        msg += i + ': ' + response.data.errors[i] + '. ';
                    }
                }

                return msg;
            },


            openKeywordEditModal() {

                if (this.editable === true) {

                    // Clone the parts that may be eddited
                    this.modifiedKeywordData = {
                        title: this.keyword.title,
                        type: this.keyword.type,
                    };

                    this.$refs.editKeyword.show();
                }
            },

            goToKeywordSearch() {
                window.location.href = this.searchLink;
            }

        }
        ,

        components: {
            ContextMenuItem: contextMenuItem,
            bModal,
            bFormInput,
            bFormSelect,
            contextMenu
        }

    }


</script>

<style scoped>
    .kw-wrapper {
        display: inline-block;
        position: relative;
        margin-bottom: 0.5rem;
        margin-right: 0.25rem;
        line-height: 1em;
    }

    .sb-keyword {
        border: 0;
    }

    .text {
        position: relative;
    }

    .sb-progress-bar {
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        border-radius: .2rem;
    }

    .icon {
        position: relative;
        display: inline-block;
        background-size: contain;
        background-position: 0 0;
        height: 0.9rem;
        background-repeat: no-repeat;
        top: 0.1rem;
        width: 1rem;
        background-image: url(/img/icons/tag.svg);
        margin-right: 0.1rem;
        margin-left: 0;
    }

    .mini {
        margin-bottom: .125rem;
        margin-top: .125rem;
        margin-left: 0;
        margin-right: .125em;
    }

    .mini .icon {
        height: 0.7rem;
        width: 0.7rem;
        margin-right: .05rem;
    }

    .mini .sb-keyword {
        font-size: 0.7em;
        padding: .125rem .25rem;
    }

</style>