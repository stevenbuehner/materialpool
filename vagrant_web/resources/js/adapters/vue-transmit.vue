<template>
  <component :is="tag">
    <div
        class="v-transmit__upload-area"
        :class="[uploadAreaClasses, draggingClasses]"
        :draggable="!disableDraggable"
        @click="handleUploadAreaClick"
        @dragenter.prevent.stop="setDragging(true, $event, 'drag-enter')"
        @dragover.prevent.stop="setDragging(true, $event, 'drag-over')"
        @dragleave="setDragging(false, $event, 'drag-leave')"
        @dragend="setDragging(false, $event, 'drag-end')"
        @drop.prevent.stop="handleDrop"
    >
      <slot/>
    </div>

    <slot name="files" v-bind="fileSlotBindings"/>

    <form class="v-transmit__hidden-form" @submit.prevent>
      <input
          ref="hiddenFileInput"
          type="file"
          :accept="acceptedFileTypes.join(',')"
          :multiple="allowsMultipleFiles"
          :capture="capture"
          @change="handleFileInput"
      >
    </form>
  </component>
</template>

<script>
import {
    createUploadFile,
    isAcceptedFileType,
    maxFileSizeInBytes,
    UploadStatus,
} from './upload-contract';

const terminalStatuses = new Set([
    UploadStatus.CANCELED,
    UploadStatus.ERROR,
    UploadStatus.TIMEOUT,
    UploadStatus.SUCCESS,
]);

export default {
    name: 'VueTransmit',

    props: {
        tag: {
            type: String,
            default: 'div',
        },
        disableDraggable: {
            type: Boolean,
            default: false,
        },
        uploadAreaClasses: {
            type: [Array, Object, String],
            default: null,
        },
        dragClass: {
            type: String,
            default: null,
        },
        maxConcurrentUploads: {
            type: Number,
            default: 2,
        },
        uploadMultiple: {
            type: Boolean,
            default: false,
        },
        maxFileSize: {
            type: Number,
            default: 256,
        },
        fileSizeBaseInBinary: {
            type: Boolean,
            default: false,
        },
        maxFiles: {
            type: Number,
            default: null,
        },
        clickable: {
            type: Boolean,
            default: true,
        },
        acceptedFileTypes: {
            type: Array,
            default: () => [],
        },
        capture: {
            type: String,
            default: null,
        },
        accept: {
            type: Function,
            default: (_file, done) => done(),
        },
        adapterOptions: {
            type: Object,
            default: () => ({}),
        },
    },

    emits: [
        'added-file',
        'added-files',
        'accepted-file',
        'rejected-file',
        'accept-complete',
        'processing',
        'sending',
        'upload-progress',
        'success',
        'error',
        'timeout',
        'complete',
        'queue-complete',
        'drag-enter',
        'drag-over',
        'drag-leave',
        'drag-end',
        'drop',
    ],

    data() {
        return {
            dragging: false,
            files: [],
            activeRequests: new Map(),
            isUnmounting: false,
        };
    },

    computed: {
        allowsMultipleFiles() {
            return this.maxFiles === null || this.maxFiles > 1;
        },
        acceptedFiles() {
            return this.files.filter(file => file.accepted);
        },
        rejectedFiles() {
            return this.files.filter(file => !file.accepted && terminalStatuses.has(file.status));
        },
        queuedFiles() {
            return this.files.filter(file => file.status === UploadStatus.QUEUED);
        },
        uploadingFiles() {
            return this.files.filter(file => file.status === UploadStatus.UPLOADING);
        },
        fileSlotBindings() {
            return {
                files: this.files,
                acceptedFiles: this.acceptedFiles,
                rejectedFiles: this.rejectedFiles,
                queuedFiles: this.queuedFiles,
                uploadingFiles: this.uploadingFiles,
                successfulFiles: this.files.filter(file => file.status === UploadStatus.SUCCESS),
                failedFiles: this.files.filter(file => file.status === UploadStatus.ERROR),
                timeoutFiles: this.files.filter(file => file.status === UploadStatus.TIMEOUT),
                activeFiles: this.files.filter(file => [UploadStatus.QUEUED, UploadStatus.UPLOADING].includes(file.status)),
                isUploading: this.uploadingFiles.length > 0,
            };
        },
        draggingClasses() {
            return {
                'v-transmit__upload-area--is-dragging': this.dragging,
                [this.dragClass]: Boolean(this.dragClass) && this.dragging,
            };
        },
    },

    beforeUnmount() {
        this.isUnmounting = true;
        this.activeRequests.forEach(xhr => xhr.abort());
        this.activeRequests.clear();
    },

    methods: {
        triggerBrowseFiles() {
            this.$refs.hiddenFileInput?.click();
        },
        handleUploadAreaClick() {
            if (this.clickable) {
                this.triggerBrowseFiles();
            }
        },
        setDragging(dragging, event, eventName) {
            this.dragging = dragging;
            this.$emit(eventName, event);
        },
        handleDrop(event) {
            this.dragging = false;
            this.$emit('drop', event);
            this.addFiles(Array.from(event.dataTransfer?.files || []));
        },
        handleFileInput(event) {
            const input = event.target;
            const files = Array.from(input.files || []);
            this.addFiles(files);
            input.value = '';
        },
        addFiles(nativeFiles) {
            const files = nativeFiles.map(nativeFile => this.addFile(nativeFile));
            this.$emit('added-files', files);
        },
        addFile(nativeFile) {
            const file = createUploadFile(nativeFile);
            this.files.push(file);
            this.$emit('added-file', file);

            this.validateFile(file, error => {
                if (error) {
                    file.status = UploadStatus.ERROR;
                    file.accepted = false;
                    this.$emit('error', file, error);
                    this.$emit('rejected-file', file);
                    this.$emit('accept-complete', file);
                    this.finishFile(file);
                    return;
                }

                file.accepted = true;
                file.status = UploadStatus.QUEUED;
                this.$emit('accepted-file', file);
                this.$emit('accept-complete', file);
                Promise.resolve().then(() => this.processQueue());
            });

            return file;
        },
        validateFile(file, done) {
            if (file.size > maxFileSizeInBytes(this.maxFileSize, this.fileSizeBaseInBinary)) {
                const base = this.fileSizeBaseInBinary ? 1024 : 1000;
                const unit = this.fileSizeBaseInBinary ? 'MiB' : 'MB';
                const actualSize = Math.round((file.size / base / base) * 10) / 10;
                done(`The file is too big (${actualSize}${unit}). Max file size: ${this.maxFileSize}${unit}.`);
                return;
            }

            if (!isAcceptedFileType(file, this.acceptedFileTypes)) {
                done(`You can't upload files of this type: ${file.type}`);
                return;
            }

            if (this.maxFiles !== null && this.acceptedFiles.length >= this.maxFiles) {
                done(`You can not upload any more files (${this.maxFiles} max).`);
                return;
            }

            this.accept(file, done);
        },
        processQueue() {
            const capacity = Math.max(0, this.maxConcurrentUploads - this.uploadingFiles.length);
            this.queuedFiles.slice(0, capacity).forEach(file => this.uploadFile(file));
        },
        uploadFile(file) {
            file.processing = true;
            file.status = UploadStatus.UPLOADING;
            this.$emit('processing', file);

            const options = this.adapterOptions;
            const xhr = new XMLHttpRequest();
            const method = options.method || 'post';

            if (!options.url) {
                this.failFile(file, 'Missing upload URL.');
                return;
            }

            xhr.open(method, options.url, true);
            xhr.timeout = options.timeout || 0;
            xhr.withCredentials = Boolean(options.withCredentials);
            xhr.responseType = options.responseType || 'json';
            Object.entries(options.headers || {}).forEach(([name, value]) => {
                if (value) {
                    xhr.setRequestHeader(name, value);
                }
            });

            xhr.upload.addEventListener('progress', event => {
                const total = event.total || file.size;
                file.upload.bytesSent = event.loaded;
                file.upload.total = total;
                file.upload.progress = Math.min(100, total > 0 ? (event.loaded / total) * 100 : 0);
                this.$emit('upload-progress', file, file.upload.progress, file.upload.bytesSent);
            });
            xhr.addEventListener('error', () => {
                this.failFile(file, `Error during upload: ${xhr.statusText} [${xhr.status}]`, xhr);
            });
            xhr.addEventListener('timeout', () => {
                this.failFile(file, 'Error during upload: the server timed out.', xhr, true);
            });
            xhr.addEventListener('load', () => {
                if (xhr.status < 200 || xhr.status >= 300) {
                    this.failFile(file, `Error during upload: ${xhr.statusText} [${xhr.status}]`, xhr);
                    return;
                }

                let response = xhr.response;
                if (options.responseParseFunc) {
                    response = options.responseParseFunc(xhr);
                }

                file.upload.bytesSent = file.upload.total;
                file.upload.progress = 100;
                file.status = UploadStatus.SUCCESS;
                file.processing = false;
                this.activeRequests.delete(file.id);
                this.$emit('success', file, response);
                this.finishFile(file);
            });

            const body = new FormData();
            Object.entries(options.params || {}).forEach(([name, value]) => body.append(name, value));
            const paramName = options.paramName || 'file';
            body.append(paramName, file.nativeFile, file.name);
            this.$emit('sending', file, xhr, body);
            this.activeRequests.set(file.id, xhr);
            xhr.send(body);
        },
        failFile(file, message, xhr, timedOut = false) {
            if (this.isUnmounting) {
                return;
            }

            file.status = timedOut ? UploadStatus.TIMEOUT : UploadStatus.ERROR;
            file.processing = false;
            this.activeRequests.delete(file.id);
            this.$emit(timedOut ? 'timeout' : 'error', file, message, xhr);
            this.finishFile(file);
        },
        finishFile(file) {
            this.$emit('complete', file);
            this.processQueue();

            Promise.resolve().then(() => {
                const unfinished = this.files.some(candidate => [
                    UploadStatus.ADDED,
                    UploadStatus.QUEUED,
                    UploadStatus.UPLOADING,
                ].includes(candidate.status));

                if (!unfinished) {
                    this.$emit('queue-complete', file);
                }
            });
        },
    },
};
</script>

<style>
.v-transmit__hidden-form {
    position: absolute !important;
    width: 0 !important;
    height: 0 !important;
    visibility: hidden !important;
}
</style>
