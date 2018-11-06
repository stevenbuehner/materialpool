<template>

    <div class="row" v-if="!editModeEnabled" @click="editModeEnabled=true">
        <div class="col-12">
            <div v-html="compiledMarkdown"/>
        </div>
    </div>
    <div class="row" v-else>
        <div class="col-6 p-3 liveEditorWrapper">
            <textarea class="liveEditor" v-model="myTextContent"></textarea>
        </div>
        <div class="col-6 p-3 livePreviewWrapper">
            <div class="livePreview" v-html="compiledMarkdown"/>
        </div>
        <div class="col-12">
            <button class="btn btn-success float-right m-1" @click="btnSave">{{$t('pool.Save')}}</button>
            <button class="btn btn-danger float-right m-1" @click="btnCancel">{{$t('pool.Cancel')}}</button>
        </div>
    </div>
</template>

<script>

    import myTextBlock from './../../my-text-block.vue';
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
                return marked(this.myTextContent, {sanitize: true})
            },
        },

        methods: {
            btnCancel() {
                this.editModeEnabled = false;
                this.myTextContent   = this.resource.content;
                this.flashInfo(this.$t('pool.Undo-changes'));
            },

            btnSave() {

                this.editModeEnabled = false;
                this.isSaving        = true;

                this.flashInfo(this.$t('pool.Saving-changes'));

                this.$store.dispatch('resources/update', {
                    id: this.resource.id,
                    data: {
                        content: this.myTextContent
                    }
                }).then((resource) => {
                    this.$emit('resource-updated', resource);
                }).catch(() => {
                    this.flash(this.$t('pool.Changes-not-saved'), 'error', {timeout: 0});
                }).then(() => {
                    this.isSaving = false;
                    this.flashSuccess(this.$t('pool.Changes-saved'));
                });
            }
        },

        components: {
            myTextBlock,
            HollowDotsSpinner,
        }

    }
</script>

<style scoped>

    .liveEditor, .livePreview {
        display: inline-block;
        vertical-align: top;
        box-sizing: border-box;
        width: 100%;
        height: 100%;
        min-height: 80vh;
    }

    .liveEditor {
        border: none;
        resize: none;
        outline: none;
        font-size: 14px;
        font-family: 'Monaco', courier, monospace;
        padding: 0;
        background-color: transparent;
    }

    .liveEditorWrapper {
        background-color: #f6f6f6;
        border-right: 1px solid #ccc;
    }

    .livePreviewWrapper {
    }

    code {
        color: #f66;
    }
</style>