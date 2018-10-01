<template>
    <div class="mb-4 mt-4">
        <vue-transmit tag="section"
                      v-bind="options"
                      upload-area-classes="bg-faded"
                      ref="uploader"
                      @success="onUploadSuccess"
                      @processing="onProcessing"
                      @timeout="onError"
                      @error="onError"
        >
            <div class="d-flex align-items-center justify-content-center w-100"
                 style="border-radius: 1rem;"
                 :style="styleObject">
                <button class="btn btn-secondary" @click="triggerBrowse" v-if="!uploadRunning">
                    <slot> {{$t('pool.Upload-resource-and-add-to-material')}}</slot>
                </button>
                <h4 v-if="uploadRunning">Upload is beeing processed</h4>
            </div>

            <template slot="files" slot-scope="props">
                <div v-for="(file, i) in props.files" :key="file.id" :class="{'mt-5': i === 0}"
                     v-if="file.status !== 'success'">
                    <h4>{{ file.name }}</h4>
                    <div class="progress" style="width: 100%;">
                        <div class="progress-bar bg-success"
                             :style="{width: file.upload.progress + '%'}"></div>
                    </div>
                </div>
            </template>

        </vue-transmit>

    </div>
</template>

<script>
    import {VueTransmit} from "vue-transmit";
    import {api_v1_resources_store} from "../serverRoutes";

    export default {
        name: "resourceUploader",

        props: {
            styleObject: {
                type: Object,
                required: false,
                default() {
                    return {};
                }
            }
        },

        data() {
            return {
                options: {
                    // acceptedFileTypes: ['image/*'],
                    clickable: false,
                    uploadMultiple: false,
                    accept: this.acceptUploadFile,
                    adapterOptions: {
                        url: api_v1_resources_store,
                        headers: {
                            'X-CSRF-TOKEN': window.Laravel.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        responseType: 'json',
                    },
                },

                uploadRunning: false
            }
        },

        methods: {

            triggerBrowse() {
                this.$refs.uploader.triggerBrowseFiles()
            },

            acceptUploadFile(file, done) {
                if (this.uploadRunning === false) {
                    done();
                }
            },

            onProcessing(file) {
                this.uploadRunning = true;
            },

            onUploadSuccess(file, resource) {
                this.$emit('resource-created', resource);
                this.uploadRunning = false;
            },

            onError(file, errorMsg) {
                console.error(errorMsg, file);
            },
        },

        filters: {
            json(value) {
                return JSON.stringify(value, null, 2)
            }
        },

        components: {
            VueTransmit
        }
    }
</script>

<style>
    .v-transmit__upload-area {
        width: 100%;
        border-radius: 0.3rem;
        border: 1px dashed #bdbdbd;
        background-color: #e9ecef;
        min-height: 5rem;
        display: flex;
    }

    @media (min-height: 1000px) {
        .v-transmit__upload-area {
            min-height: 300px;
        }
    }

    .v-transmit__upload-area--is-dragging {
        background: #e1f5fe linear-gradient(
                -45deg,
                #fafafa 25%,
                transparent 25%,
                transparent 50%,
                #fafafa 50%,
                #fafafa 75%,
                transparent 75%,
                transparent
        );
        background-size: 40px 40px;
    }
</style>