<template>
    <div class="bibleverse-wrapper">
        <div class="btn btn-sm btn-secondary sb-bibleverse"
             :class="{'tag-readonly' : !editable, 'tag-editable' : editable}"
             @mousedown.left.prevent="startDrag"
             :style="{color: theme.colors.color, backgroundColor: theme.colors.background}">
            <div class="sb-progress-bar" :style="styleObject"></div>
            <span class="icon" :style="{backgroundImage: 'url('+ bibleverse.icon+')'}"></span>
            <span class="text">{{ bibleverse.label }}</span>
        </div>
    </div>
</template>


<script>

    import {draggingSupport} from "../keyword/dragging.mixin";
    import {tagging} from './../theme';

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
            }
        },


        data: function () {
            return {};
        },

        computed: {

            theme() {
                return tagging;
            },

            relevance() {
                if (this.dragging.ongoing === true) {
                    return this.dragDifference;
                } else {
                    return this.bibleverse.pivot.relevance;
                }
            },
            styleObject: function () {
                return {
                    width: this.relevance / 300 * 100 + '%',
                    backgroundColor: this.dragging.ongoing === true ? this.theme.colors.progressbar.dragging : this.theme.colors.progressbar.default,
                }
            },
            bibleverseUpdatePivotApiUrl() {
                return '/api/v1/material/' + this.materialId + '/bibleverse/' + this.bibleverse.id;
            },
        },

        created: function () {


        },

        methods: {
            updatePivot(pivot) {
                this.$emit('savingPivot', {pivot: pivot});

                pivot._method = 'PUT';

                return axios.post(this.bibleverseUpdatePivotApiUrl, pivot)
                    .then((response) => {
                            // Success
                            this.$emit('savedPivot', {
                                oldBibleverse: this.bibleverse,
                                newBibleverse: response.data // response contains pivot-data
                            });
                        }
                    ).catch((response) => {
                        this.$emit('savingPivotError', {
                            tag: this.bibleverse,  // "tag" is used for bibleverses and keywords
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
        },

    }


</script>

<style scoped>

    .bibleverse-wrapper {
        float: left;
        position: relative;
        margin-bottom: 0.5rem;
        margin-right: 0.25rem;
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

    input {

    }

</style>