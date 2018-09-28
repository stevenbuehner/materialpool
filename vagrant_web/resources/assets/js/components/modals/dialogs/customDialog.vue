<template>
    <b-modal
            :size="opt.size"
            lazy
            ref="myModal"
            centered
            :busy="opt.busy"
            @hide="onHide"
            @cancel="onCancel"
            :no-close-on-esc="!opt.allowBackdrop"
            :no-close-on-backdrop="!opt.allowBackdrop"
            :hide-header-close="!opt.allowBackdrop"
    >
        <slot>
            <div v-html="opt.content"></div>
        </slot>

        <template slot="modal-title">{{opt.title}}</template>

        <template slot="modal-footer">
            <b-button :variant="opt.yesVariant" v-if="opt.yesEnabled" @click="btnYes">
                <slot name="modal-yes">{{opt.yesText}}</slot>
            </b-button>
            <b-button :variant="opt.noVariant" v-if="opt.noEnabled" @click="btnNo">
                <slot name="modal-no">{{opt.noText}}</slot>
            </b-button>
            <b-button :variant="opt.cancelVariant" v-if="opt.cancelEnabled" @click="btnCancel">
                <slot name="modal-cancel">{{opt.cancelText}}</slot>
            </b-button>
            <div class="keep-empty-placeholder"></div>
        </template>

    </b-modal>
</template>

<script>
    import bModal from 'bootstrap-vue/src/components/modal/modal';
    import bButton from 'bootstrap-vue/src/components/button/button';

    export default {
        name: "customDialog",

        props: {
            options: {
                type: Object,
                required: false,
                default() {
                    return {};
                }
            }
        },

        data() {
            return {

                defaultOptions: {
                    title: 'Dialog',
                    size: 'lg',
                    busy: false,
                    content: 'Wollen Sie wirklich?',
                    yesText: this.$t('pool.Yes'),
                    yesVariant: 'primary',
                    yesResult: true,
                    yesEnabled: true,
                    noText: this.$t('pool.No'),
                    noVariant: 'danger',
                    noResult: false,
                    noEnabled: true,
                    cancelText: this.$t('pool.Cancel'),
                    cancelVariant: 'warning',
                    cancelResult: null,
                    cancelEnabled: false,
                    allowBackdrop: true,
                },

                // Will be overridden with defaultOptions (!)
                opt: {
                    title: null,
                    size: null,
                    busy: null,
                    content: null,
                    yesText: null,
                    yesVariant: null,
                    yesResult: null,
                    yesEnabled: null,
                    noText: null,
                    noVariant: null,
                    noResult: null,
                    noEnabled: null,
                    cancelText: null,
                    cancelVariant: null,
                    cancelResult: null,
                    cancelEnabled: null,
                    allowBackdrop: null,
                },

                promise: null,
                resolve: null,
                reject: null,
            };
        },

        created() {

            for (let i in this.options) {
                this.defaultOptions[i] = this.options[i];
            }

        },

        methods: {

            btnYes(event) {
                this.opt.busy = true;
                this.$emit('onBtnYes');
                this.hideSuccessfull(this.opt.yesResult);
            },

            btnNo(event) {
                this.opt.busy = true;
                this.$emit('onBtnNo');
                this.hideSuccessfull(this.opt.noResult);
            },

            btnCancel(event) {
                this.opt.busy = true;
                this.$emit('onBtnCancel');
                this.hideWithCancel(this.opt.cancelResult);
            },

            onHide(event) {
                this.opt.busy = true;
                this.$emit('onHide');
                this.cancelPromise('closed early');
            },

            onCancel(event) {
                this.opt.busy = true;
                this.$emit('onCancel');
                this.hideWithCancel('closed early');
            },

            initWithOptions(tempOptions) {

                tempOptions = tempOptions || {};

                for (let i in this.defaultOptions) {

                    if (tempOptions.hasOwnProperty(i)) {
                        this.$set(this.opt, i, tempOptions[i]);
                        // this.opt[i] = tempOptions[i];
                    } else if (this.defaultOptions.hasOwnProperty(i)) {
                        // this.opt[i] = this.defaultOptions[i];
                        this.$set(this.opt, i, this.defaultOptions[i]);

                    }
                }

            },

            show(options) {

                if (this.promise !== null) {
                    this.cancelPromise("next modal wan't to be opened");
                }

                this.initWithOptions(options);

                return this.promise = new Promise((resolve, reject) => {
                    this.resolve = resolve;
                    this.reject  = reject;
                    this.$refs.myModal.show();
                });

            },

            hide() {
                this.hideWithCancel('closed externaly');

                return this;
            },

            hideSuccessfull(data) {

                this.resolvePromiseSuccessfully(data);
                this.$refs.myModal.hide();

            },

            resolvePromiseSuccessfully(data) {

                if (typeof this.resolve === 'function') {
                    this.resolve(data);

                    this.resolve = null;
                    this.reject  = null;
                    this.promise = null;
                }

            },

            hideWithCancel(data) {

                this.cancelPromise(data);
                this.$refs.myModal.hide();

            },

            cancelPromise(data) {

                if (typeof this.reject === 'function') {
                    this.reject(data);

                    this.resolve = null;
                    this.reject  = null;
                    this.promise = null;
                }

            },

        },

        components: {
            bModal,
            bButton
        }
    }
</script>

<style scoped>

</style>