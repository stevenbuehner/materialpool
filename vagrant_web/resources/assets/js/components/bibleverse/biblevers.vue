<template>
    <div class="bibleverse-wrapper" :class="[size]">
        <div class="btn btn-sm btn-secondary sb-bibleverse"
             :class="{'tag-readonly' : !editable, 'tag-editable' : editable}"
             @mousedown.left.prevent="startDrag"
             @click.right.prevent="$refs.menu.openMenu($event)"
             :style="{color: theme.colors.color, backgroundColor: theme.colors.background}">
            <div class="sb-progress-bar" :style="styleObject"></div>
            <span class="icon" :style="{backgroundImage: 'url('+ myBibleverse.icon+')'}"></span>
            <span class="text">{{ myBibleverse.label }}</span>
        </div>

        <context-menu ref="menu">
            <context-menu-item @click="">Suche nach '{{myBibleverse.label}}'</context-menu-item>
            <context-menu-item v-if="removeable" @click.prevent="removeBibleverse">löschen</context-menu-item>
        </context-menu>
    </div>
</template>


<script>

    import {draggingSupport} from "../keyword/dragging.mixin";
    import {tagging} from './../theme';
    import contextMenu from './../context-menu/context-menu.vue';
    import contextMenuItem from "../context-menu/context-menu-item.vue";
    import {bibleverseUpdatePivotRoute, materialRemoveBibleverseRoute} from './../serverRoutes';

    import axios from 'axios';

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
                required: true
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
                } else {
                    return this.myBibleverse.pivot.relevance;
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
            updatePivot(pivot) {
                this.$emit('savingPivot', {pivot: pivot});

                pivot._method = 'PUT';

                return axios.post(bibleverseUpdatePivotRoute(this.materialId, this.myBibleverse.id), pivot)
                    .then((response) => {
                            // on success

                            // Update this bibleverse data directly
                            this.myBibleverse.pivot = response.data.pivot;

                            this.emitSaved(this.myBibleverse);

                        }
                    ).catch((response) => {
                        this.$emit('savingPivotError', {
                            tag: this.myBibleverse,  // "tag" is used for bibleverses and keywords
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

            emitSaved(newBibleverse) {
                this.$emit('saved', newBibleverse);
            },

            emitRemoved() {
                this.$emit('removed', this.myBibleverse);
            },

            removeBibleverse() {

                let params = {
                    _method: 'DELETE'
                };

                axios.post(materialRemoveBibleverseRoute(this.materialId, this.myBibleverse.id), params)
                    .then((response) => {
                            this.emitRemoved();
                        }
                    ).catch((response) => {
                    // on failure
                    console.error('Failed to remove bibleverse', this.myBibleverse);
                });

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