<template>
    <div class="bibleverse-wrapper" :class="[size]">
        <div class="btn btn-sm btn-secondary sb-bibleverse"
             :class="{'tag-readonly' : !editable, 'tag-editable' : editable}"
             @mousedown.left.prevent="startDrag"
             @click.right.prevent="$refs.menu.openMenu($event)"
             role="button"
             :style="{color: theme.colors.color, backgroundColor: theme.colors.background}">
            <div class="sb-progress-bar" :style="styleObject"></div>
            <span class="icon" :style="{backgroundImage: 'url('+ myBibleverse.icon+')'}"></span>
            <span class="text">{{ myBibleverse.label }}</span>
        </div>

        <context-menu ref="menu">
            <context-menu-item @click.prevent="searchForBibleverse">Suche nach '{{myBibleverse.label}}'
            </context-menu-item>
            <context-menu-item v-if="removeable" @click.prevent="removeBibleverse">entfernen</context-menu-item>
        </context-menu>
    </div>
</template>


<script>

    import {draggingSupport} from "../keyword/dragging.mixin";
    import {tagging} from './../theme';
    import contextMenu from './../context-menu/context-menu.vue';
    import contextMenuItem from "../context-menu/context-menu-item.vue";
    import {bibleverseSearchLink} from './../serverRoutes';

    export default {

        mixins: [
            draggingSupport
        ],

        props: {
            bibleverse: {
                type: Object,
                required: true
            },

            materialId: {
                type: Number,
                required: false
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
            },

            removeable: {
                type: Boolean,
                required: false,
                default: false
            },
        },


        model: {
            prop: 'bibleverse',
            event: 'saved'
        },


        data: function () {
            return {
                myBibleverse: {},
            };
        },

        computed: {

            theme() {
                return tagging;
            },

            relevance() {
                if (this.dragging.ongoing === true) {
                    return this.dragDifference;
                } else if (this.myBibleverse.pivot) {
                    return this.myBibleverse.pivot.relevance;
                } else {
                    return 0;
                }
            },
            styleObject: function () {
                return {
                    width: this.relevance / 300 * 100 + '%',
                    backgroundColor: this.dragging.ongoing === true ? this.theme.colors.progressbar.dragging : this.theme.colors.progressbar.default,
                }
            }
        },

        created: function () {

            // Needs to be copied. Because any changes in properties are not recognized in computed properties
            this.myBibleverse = JSON.parse(JSON.stringify(this.bibleverse));
        },

        methods: {

            /* used by mixin */
            updateRelevance(relevance) {

                this.$emit('savingPivot', {relevance: relevance});

                if (this.materialId) {
                    this.$store.dispatch('bibleverses/updateRelevance', {
                        materialId: this.materialId,
                        bibleverseId: this.myBibleverse.id,
                        relevance: relevance
                    }).then((bibleverse) => {
                        // Update this bibleverse data directly
                        this.myBibleverse.pivot = bibleverse.pivot;
                        this.emitSaved(bibleverse);
                    }).catch((response) => {
                        this.$emit('savingPivotError', {
                            tag: this.myBibleverse,  // "tag" is used for bibleverses and keywords
                            msg: this.parseResponseErrors(response.response)
                        });
                    });
                }
                else {

                    console.info('Can not add bibleverse to material at the server because no materialId given', this.myBibleverse);

                    let pivot               = this.myBibleverse.pivot || {};
                    pivot.relevance         = relevance;
                    this.myBibleverse.pivot = pivot;

                    this.emitSaved(this.myBibleverse);

                }


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

            emitSaved(newBibleverse) {
                this.$emit('saved', newBibleverse);
            },

            emitRemoved() {
                this.$emit('removed', this.myBibleverse);
            },

            searchForBibleverse() {
                window.location.href = bibleverseSearchLink(this.myBibleverse);
            },

            removeBibleverse() {

                if (this.materialId) {

                    this.$store.dispatch('bibleverses/deleteAssignment', {
                        materialId: this.materialId,
                        bibleverseId: this.myBibleverse.id
                    }).then(() => {
                        this.emitRemoved();
                    }).catch((response) => {
                        console.error('Failed to remove bibleverse', this.myBibleverse);
                    });

                } else {

                    console.info('Can not remove bibleverse from material at the server because no materialId given', this.myBibleverse);
                    this.emitRemoved();

                }


            }
        },

        components: {
            contextMenu,
            contextMenuItem
        }

    }


</script>

<style scoped>

    .bibleverse-wrapper {
        display: inline-block;
        position: relative;
        margin-bottom: 0.5rem;
        margin-right: 0.25rem;
        line-height: 1em;
    }

    .sb-bibleverse {
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

    .mini .sb-bibleverse {
        font-size: 0.7em;
        padding: .125rem .25rem;
    }


</style>