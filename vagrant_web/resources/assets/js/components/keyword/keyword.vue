<template>
    <div class="kw-wrapper" :class="[size]">
        <div class="btn btn-sm btn-secondary sb-keyword"
             :class="{'tag-readonly' : !editable, 'tag-editable' : editable}"
             @click.right.prevent="$refs.menu.openMenu($event)"
             @mousedown.left.prevent="startDrag"
             role="button"
             :style="{color: theme.colors.color, backgroundColor: theme.colors.background}">
            <div v-if="hasPivot" class="sb-progress-bar" :style="styleObject"></div>
            <span class="icon" :style="{backgroundImage : 'url(' + keyword.icon + ')'}"></span>
            <span class="text">{{ keyword.title }}</span>
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
                                v-model="modifiedKeyword.title"
                                autofocus
                        ></b-form-input>
                    </div>
                </div>

                <div class="row">
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
            <context-menu-item @click="goToKeywordSearch">Suche nach '{{keyword.title}}'</context-menu-item>
            <context-menu-item v-if="editable" @click="openKeywordEditModal">Alle Tags umbenennen</context-menu-item>
        </context-menu>

    </div>
</template>


<script>
    import bModal from 'bootstrap-vue/es/components/modal/modal';
    import bFormInput from 'bootstrap-vue/es/components/form-input/form-input';
    import contextMenu from './../context-menu/context-menu.vue';
    import contextMenuItem from "../context-menu/context-menu-item.vue";
    import axios from 'axios';
    import {tagging} from './../theme';

    import {draggingSupport} from "./dragging.mixin";

    export default {

        mixins: [
            draggingSupport
        ],

        props: {
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
            }
        },


        data: function () {
            return {
                menuIsOpen: false,
                modifiedKeyword: {},
            };
        },

        computed: {

            theme() {
                return tagging;
            },

            searchLink() {
                return '/pool/keyword/' + this.keyword.lc_title;
            },

            relevance() {
                if (this.dragging.ongoing === true) {
                    return this.dragDifference;
                } else {
                    return this.keyword.pivot.relevance;
                }
            },

            hasPivot() {
                return this.keyword.pivot !== undefined && this.keyword.pivot.relevance !== undefined;
            },


            styleObject: function () {
                return {
                    width: this.relevance / 300 * 100 + '%',
                    backgroundColor: this.dragging.ongoing === true ? this.theme.colors.progressbar.dragging : this.theme.colors.progressbar.default,
                }
            },

            keywordUpdateApiUrl() {
                return '/api/v1/keywords/' + this.keyword.id;
            },

            keywordUpdatePivotApiUrl() {
                return '/api/v1/material/' + this.materialId + '/keyword/' + this.keyword.id;
            },

        },

        created: function () {

            // Clone the parts that may be eddited
            this.modifiedKeyword = {
                title: this.keyword.title,
            };

        },

        methods: {

            storeModalChanges() {
                if (this.keyword.title != this.modifiedKeyword.title) {
                    // this.$emit('dataChanged', {title: this.modifiedKeyword.title});
                    this.updateKeywordData({title: this.modifiedKeyword.title})
                }
            },

            updateKeywordData(properties) {

                this.$emit('saving', properties);

                properties._method = 'PUT';

                return axios.post(this.keywordUpdateApiUrl, properties)
                    .then((response) => {
                            // on success

                            // Update this keyword directly
                            for (let i in properties) {
                                if (i !== 'PUT' && response.data[i] !== undefined) {
                                    this.keyword[i] = response.data[i];
                                }
                            }

                            // And also offer the parent the option to update the data
                            // The parent may then repopulate the props.keyword
                            this.$emit('saved', {
                                oldKeyword: this.keyword,
                                newKeyword: {...response.data, pivot: this.keyword.pivot} // response misses pivot-data
                            });
                        }
                    ).catch((response) => {
                        // on failure
                        this.$emit('savingError', {
                            tag: this.keyword, // "Tag" is used for bibleverses and keywords
                            msg: this.parseResponseErrors(response.response)
                        });
                    });

            },

            updatePivot(pivot) {
                this.$emit('savingPivot', {pivot: pivot});

                pivot._method = 'PUT';

                return axios.post(this.keywordUpdatePivotApiUrl, pivot)
                    .then((response) => {
                            // on success

                            // Update this keyword directly
                            this.keyword.pivot = response.data.pivot;

                            // And also offer the parent the option to update the data
                            // The parent may then repopulate the props.keyword
                            this.$emit('savedPivot', {
                                oldKeyword: this.keyword,
                                newKeyword: response.data // response contains pivot-data
                            });
                        }
                    ).catch((response) => {
                        // on failure
                        this.$emit('savingPivotError', {
                            tag: this.keyword,  // "tag" is used for bibleverses and keywords
                            msg: this.parseResponseErrors(response.response)
                        });
                    });
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
                this.$refs.editKeyword.show();
            },

            goToKeywordSearch() {
                console.log('Go to search');
                window.location.href = this.searchLink;
            }

        },

        components: {
            ContextMenuItem: contextMenuItem,
            bModal,
            bFormInput,
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