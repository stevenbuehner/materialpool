<template>

    <div class="row" v-if="!editModeEnabled" @dblclick="editModeEnabled=true">
        <div class="col-12">
            <div class="sbMarkedStyle" v-html="compiledMarkdown"/>
            <button class="btn btn-sm btn-primary" @click.stop="editModeEnabled=true"
                    v-if="almostNoContentToEditVisible">
                {{$t('pool.Edit')}}
            </button>
        </div>
    </div>
    <div class="row" v-else>
        <div class="col-6 p-3 liveEditorWrapper" :class="{savingNeccessary}">
            <textarea
                    class="liveEditor"
                    v-model="myTextContent"
                    @keydown.meta.enter.exact="btnSave"
                    @keyup.esc.exact="btnCancelIfNothingChanged"></textarea>
        </div>
        <div class="col-6 p-3 livePreviewWrapper">
            <div class="livePreview sbMarkedStyle" v-html="compiledMarkdown"/>
        </div>
        <div class="col-12">
            <button
                    class="btn btn-sm btn-success float-right m-1"
                    @click="btnSave"
                    v-show="savingNeccessary"
                    title="CMD + ENTER"
            >{{$t('pool.Save')}}
            </button>
            <button
                    class="btn btn-sm btn-danger float-right m-1"
                    @click="btnCancel"
                    title="ESC"
            >{{$t('pool.Cancel')}}
            </button>
        </div>
    </div>
</template>

<script>

    import myTextBlock from '../../my-text-block.vue';
    import marked from 'marked';
    import {HollowDotsSpinner} from 'epic-spinners'

    export default {
        mixins: [],

        props: {
            resource: {
                required: true,
                type: Object
            }
        },

        data() {
            return {
                editModeEnabled: false,
                isSaving: false,
                myTextContent: this.resource.content,
            };
        },

        computed: {
            compiledMarkdown() {
                return marked(this.myTextContent, {sanitize: true, gfm: false, smartLists: true, smartypants: true})
            },

            almostNoContentToEditVisible() {
                return this.myTextContent.length <= 5;
            },

            savingNeccessary() {
                return this.myTextContent !== this.resource.content;
            },
        },

        methods: {
            btnCancel() {
                this.editModeEnabled = false;
                this.myTextContent   = this.resource.content;
                this.flashInfo(this.$t('pool.Undo-changes'));
            },

            btnCancelIfNothingChanged() {
                if (!this.savingNeccessary) {
                    this.btnCancel();
                }
            },

            btnSave() {

                this.editModeEnabled = false;
                this.isSaving        = true;

                this.flashInfo(this.$t('pool.Saving-content-changes'));

                this.$store.dispatch('resources/update', {
                    id: this.resource.id,
                    data: {
                        content: this.myTextContent
                    }
                }).then((resource) => {
                    this.$emit('resource-updated', resource);
                }).catch(() => {
                    this.flash(this.$t('pool.Content-not-saved'), 'error', {timeout: 0});
                }).then(() => {
                    this.isSaving = false;
                    this.flashSuccess(this.$t('pool.Content-saved'));
                });
            }
        },

        components: {
            myTextBlock,
            HollowDotsSpinner,
        }

    }
</script>

<style type="scss">

    @import "../../../../sass/theme";

    .liveEditor, .livePreview {
        display: inline-block;
        vertical-align: top;
        box-sizing: border-box;
        width: 100%;
        height: 100%;
        min-height: 80vh;
    }

    .liveEditorWrapper {
        background-color: #f6f6f6;
        border: 1px solid #f6f6f6;
        border-right: 1px solid #ccc;

        .liveEditor {
            border: none;
            resize: none;
            outline: none;
            font-size: 14px;
            font-family: 'Monaco', courier, monospace;
            padding: 0;
            background-color: transparent;
        }

        &.savingNeccessary {
            border-color: $red;
        }

    }

    .livePreviewWrapper {
    }

    .sbMarkedStyle {

        a {
            color: #4183c4;
            text-decoration: none;
        }

        code {
            display: block;
            overflow: auto;
            margin: 15px 0;
            padding: 1em 1em;
            background-color: #f8f8f8;
            font-size: 1em;
            line-height: 1.25em;
            border: 1px solid #ddd;
            border-radius: 3px;
            color: inherit;
            font-family: monospace;
        }

        blockquote {
            border-left: 4px solid #DDD;
            padding: 0 15px;
            color: #777;
        }
    }


</style>