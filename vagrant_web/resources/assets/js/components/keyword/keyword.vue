<template>
    <div class="kw-wrapper" :class="[size]">
        <div class="btn btn-sm btn-secondary sb-keyword"
             :class="{'tag-readonly' : !editable, 'tag-editable' : editable}"
             @mousedown.left.prevent="startDrag"
             @click.right="openRightClickMenu"
             @dblclick.prevent="openKeywordEditModal"
             role="button">
            <div class="sb-progress-bar" :class="{isDragging : dragging.ongoing}" :style="styleObject"></div>
            <component :is="iconName" class="icon"></component>
            <span class="text">{{ myKeyword.title }}</span>
            <span class="delete" v-if="removeable" @mousedown.left.stop @click.prevent.stop="removeKeyword">x</span>
        </div>

        <b-modal ref="editKeyword" lazy title="Edit Keyword" @ok="storeModalChanges">
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
            <context-menu-item v-if="searchable" @click="goToKeywordSearch">nach '{{myKeyword.title}}' suchen
            </context-menu-item>
            <context-menu-item v-if="editable" @click="openKeywordEditModal">bearbeiten</context-menu-item>
        </context-menu>

    </div>
</template>


<script>
    import bModal from 'bootstrap-vue/es/components/modal/modal';
    import bFormInput from 'bootstrap-vue/es/components/form-input/form-input';
    import bFormSelect from 'bootstrap-vue/es/components/form-select/form-select';
    import contextMenu from './../context-menu/context-menu.vue';
    import contextMenuItem from "../context-menu/context-menu-item.vue";
    import {keywordSearchLink} from './../serverRoutes';
    import {ayceIcon, iconName, keyIcon, langIcon, personIcon, placeIcon} from './keywordDefaultIcons';

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
                required: false
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

            searchable: {
                type: Boolean,
                required: false,
                default: true
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

            searchLink() {
                return keywordSearchLink(this.myKeyword);
            },

            relevance() {
                if (this.dragging.ongoing === true) {
                    return this.dragDifference;
                } else if (this.myKeyword.pivot) {
                    return this.myKeyword.pivot.relevance;
                } else {
                    return 0;
                }
            },

            styleObject: function () {
                return {
                    width: this.relevance / 300 * 100 + '%',
                }
            },

            iconName() {
                return iconName(this.myKeyword);
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

                const promise = this.$store.dispatch('keywords/update', {id: this.keyword.id, data: properties});

                promise.then((keyword) => {

                    for (let prop in keyword) {
                        // This is actually only neccessary, if the parent does not update the keyword anyway
                        this.myKeyword[prop] = keyword[prop];
                    }

                    this.emitSaved(this.myKeyword);
                }).catch((response) => {

                    // on failure
                    this.$emit('savingError', {
                        tag: this.keyword, // "Tag" is used for bibleverses and keywords
                        msg: this.parseResponseErrors(response.response)
                    });
                });

            },

            /* used by mixin */
            updateRelevance(relevance) {

                this.$emit('savingPivot', {relevance: relevance});

                if (this.materialId) {

                    const promise = this.$store.dispatch('keywords/updateRelevance', {
                        materialId: this.materialId,
                        keywordId: this.keyword.id,
                        relevance: relevance
                    });

                    promise.then((keyword) => {

                        // Nur für den Fall, dass die Komponente irgendwo eingesetzt wird, wo sich das im Hintergrund nicht aktualisiert
                        this.myKeyword.pivot = keyword.pivot;

                        this.emitSaved(keyword);

                    }).catch((response) => {

                        // on failure
                        this.$emit('savingPivotError', {
                            tag: this.keyword,  // "tag" is used for bibleverses and keywords
                            msg: this.parseResponseErrors(response.response)
                        });
                    });
                } else {

                    console.info('Relevance can only be changed in the backend when a material-id is given!');

                    let pivot            = this.myKeyword.pivot || {};
                    pivot.relevance      = relevance;
                    this.myKeyword.pivot = pivot;

                    this.emitSaved(this.myKeyword);

                }


            },

            removeKeyword() {

                if (this.materialId) {
                    this.$store.dispatch('keywords/deleteAssignment', {
                        materialId: this.materialId,
                        keywordId: this.myKeyword.id
                    })
                        .then((response) => {
                            this.$emit('removed', this.myKeyword);
                        });
                } else {
                    console.info('Missing MaterialID -> The association is only removed in frontend!');
                    this.$emit('removed', this.myKeyword);
                }


            },

            emitSaved(newKeyword) {
                this.$emit('saved', newKeyword);
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
            },

            openRightClickMenu(event) {
                if (this.searchable || this.editable || this.removeable) {
                    this.$refs.menu.openMenu(event)
                }
            },

        },

        components: {
            ContextMenuItem: contextMenuItem,
            bModal,
            bFormInput,
            bFormSelect,
            contextMenu,
            keyIcon,
            placeIcon,
            personIcon,
            langIcon: langIcon,
            ayceIcon,
        }

    }


</script>

<style scoped type="scss">

    @import "resources/assets/sass/theme.scss";

    .kw-wrapper {
        display: inline-block;
        position: relative;
        margin-bottom: 0.5rem;
        margin-right: 0.25rem;
        line-height: 1em;
        color: $tag-font-colour;
    }

    .sb-keyword {
        border: 0;
        background-color:  $tag-background-colour;
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
        background-color: $tag-progressbar-default-colour;
    }

    .sb-progress-bar.isDragging{
        background-color: $tag-progressbar-dragging-colour;
    }

    .icon {
        position: relative;
        height: 1rem;
        margin-right: 0.1rem;
        top: -.1rem;
    }

    .icon >>> path {
        fill: black;
        stroke: black;
    }

    .delete {
        position: relative;
        color: whitesmoke;
        padding-left: 0.25em;
        font-weight: bold;
        cursor: pointer;
    }

    .delete:hover {
        color: black;
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
