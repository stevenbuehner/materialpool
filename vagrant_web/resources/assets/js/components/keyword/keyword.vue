<template>
    <div class="kw-wrapper">
        <button class="sb-keyword btn btn-sm btn-secondary sb-keyword-textview"
                :class="{'tag-readonly' : !editable, 'tag-editable' : editable}"
                @click.right.prevent="$refs.menu.openMenu($event)"
                @mousedown.left="startDrag"
                role="button">
            <div class="sb-progress-bar" :style="styleObject"></div>
            <span class="icon" :style="{backgroundImage : 'url(' + keyword.icon + ')'}"></span>
            <span class="text">{{ keyword.title }}</span>
        </button>

        <b-modal ref="editKeyword" title="Edit Keyword" @ok="storeModalChanges">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-3">
                        <label for="keywordText">Label:</label>
                    </div>
                    <div class="col-sm-9">
                        <b-form-input
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
            <context-menu-item @click="goToKeywordSearch">Neue Suche</context-menu-item>
            <context-menu-item @click="openKeywordEditModal">Alle Tags umbenennen</context-menu-item>
            <context-menu-item disabled>Deaktiviert</context-menu-item>
        </context-menu>

    </div>
</template>


<script>
    import bModal from 'bootstrap-vue/es/components/modal/modal';
    import bFormInput from 'bootstrap-vue/es/components/form-input/form-input';
    import contextMenu from './../context-menu/context-menu.vue';
    import contextMenuItem from "../context-menu/context-menu-item.vue";
    import axios from 'axios';

    export default {

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
                default: 'small'
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

                dragging: {
                    dragging: false,
                    xStart: 0,
                    xEnd: 0,
                    backupRelevance: 0
                }
            };
        },

        computed: {

            searchLink() {
                return '/pool/keyword/' + this.keyword.lc_title;
            },

            relevance() {
                if (this.dragging.dragging === true) {
                    return this.dragDifference;
                } else {
                    return this.keyword.pivot.relevance;
                }
            },

            dragDifference() {
                return Math.min(Math.max(this.dragging.xEnd - this.dragging.xStart, 0), 300);
            },

            styleObject: function () {
                return {
                    width: this.relevance / 300 * 100 + '%',
                    backgroundColor: this.dragging.dragging === true ? '#218838' : '#97eac8'
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
                pivot: {
                    relevance: this.keyword.pivot.relevance
                }
            };

        },

        methods: {

            startDrag(event) {
                this.dragging.dragging = true;
                this.dragging.xStart   = this.dragging.xEnd = event.clientX;

                window.addEventListener('mouseup', this.stopDrag);
                window.addEventListener('mousemove', this.doDrag);
                window.addEventListener('keydown', this.keydown)

            },
            doDrag(event) {
                this.dragging.xEnd = event.clientX;
            },
            stopDrag(event) {

                // Remove Event Listeners
                window.removeEventListener('mouseup', this.stopDrag);
                window.removeEventListener('mousemove', this.doDrag);
                window.removeEventListener('keydown', this.keydown);


                if (this.dragging.dragging /* true if dragging was not canceled */
                    && event /* Event exists when dragging was not canceled */
                ) {
                    this.doDrag(event); // Use the last mouse coordinates
                    this.updateKeywordPivot({relevance: this.dragDifference})
                    this.dragging.dragging = false;
                }


            },
            cancelDrag() {
                this.dragging.dragging = false;
                this.stopDrag();
            },
            keydown(event) {
                event = event || window.event;
                if (event.keyCode === 27) {
                    // ESC Pressed
                    this.cancelDrag();
                }
            },

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
                            // Success
                            this.$emit('saved', {
                                oldKeyword: this.keyword,
                                newKeyword: {...response.data, pivot: this.keyword.pivot} // response misses pivot-data
                            });
                        }
                    ).catch((response) => {
                        this.$emit('savingError', {
                            keyword: this.keyword,
                            msg: this.parseResponseErrors(response.response)
                        });
                    });

            },

            updateKeywordPivot(pivot) {
                this.$emit('savingPivot', {pivot: pivot});

                pivot._method = 'PUT';

                return axios.post(this.keywordUpdatePivotApiUrl, pivot)
                    .then((response) => {
                            // Success
                            this.$emit('savedPivot', {
                                oldKeyword: this.keyword,
                                newKeyword: response.data // response contains pivot-data
                            });
                        }
                    ).catch((response) => {
                        this.$emit('savingPivotError', {
                            keyword: this.keyword,
                            msg: this.parseResponseErrors(response.response)
                        });
                    });
            },

            parseResponseErrors(response) {
                var msg = 'Error! ';

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
        float: left;
    }

    .sb-keyword {
        position: relative;
        margin-bottom: 0.5rem;
        margin-right: 0.25rem;
        border: 0;
    }

    .sb-keyword .text {
        position: relative;
    }

    .sb-keyword > .sb-progress-bar {
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        border-radius: .2rem;
    }

    .sb-keyword .icon {
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

    input {

    }

</style>