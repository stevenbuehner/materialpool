<template>
    <div class="sideBarField textEditSidebarField">

        <div class="label">
            <slot name="label">
                <slot name="icon">
                    <text-field-icon/>
                </slot>

                <span class="title">
                    <slot name="title">{{name}}</slot>
                </span>
            </slot>

            <b-button
                    v-if="valueHasChanged && enableSaveButton"
                    size="sm"
                    class="save-button"
                    @click="sendSaveRequest">
                <slot name="save-button">{{$t('pool.save')}}</slot>
            </b-button>
        </div>

        <div class="editField">

            <slot name="input">
                <b-form-input
                        v-if="type == 'text' || type == 'date'"
                        class="input textInput"
                        @input="onInputChanged"
                        @keyup.enter="onEnter"
                        :value="currentValue"
                        :type="type"
                        :placeholder="getPlaceholder"
                        :disabled="disabled"
                        size="sm"
                        ref="input_field"
                ></b-form-input>

                <b-form-textarea
                        v-if="type == 'textarea'"
                        class="input textareaInput"
                        :placeholder="getPlaceholder"
                        :rows="rows"
                        :value="currentValue"
                        :max-rows="8"
                        :disabled="disabled"
                        @input="onInputChanged"
                        @keyup.enter="onEnter"
                        ref="input_field"
                ></b-form-textarea>

            </slot>

        </div>

    </div>
</template>

<script>
    import Vue from 'vue';
    import {FormInputPlugin, FormTextareaPlugin} from 'bootstrap-vue';
    import textFieldIcon from 'svg-icon/dist/svg/material/text-fields.svg'
    import generalMixin from './generalSidebarFields.mixin';
    import bButton from 'bootstrap-vue/src/components/button/button';

    Vue.use(FormTextareaPlugin);
    Vue.use(FormInputPlugin);

    export default {
        name: "textEdit",

        mixins: [generalMixin],

        props: {
            type: {
                type: String,
                required: false,
                default: 'text',
                validator(value) {

                    switch (value) {
                        case 'text':
                        case 'date':
                        case 'textarea':
                            return true;
                        default:
                            return false;
                    }

                }
            },

            enableSaveButton: {
                type: Boolean,
                required: false,
                default: true
            },

            /** Nur für type='textarea' */
            rows: {
                type: Number,
                required: false,
                default: 2
            },

        },

        data() {
            return {
                currentValue: ''
            };
        },

        watch: {
            value: {
                handler(newValue) {
                    this.currentValue = newValue;
                },
                immediate: true
            }
        },

        methods: {
            onInputChanged(currentValue) {
                this.currentValue = currentValue.trim();

                if (this.valueHasChanged) {
                    this.$emit('input', currentValue.trim());
                }
            },

            onEnter(event) {
                this.$emit('on-enter', this.currentValue);
                this.sendSaveRequest();
            },

            sendSaveRequest() {
                this.$emit('save-request', this.currentValue);
            }
        },

        computed: {
            // Das funktioniert nur, wenn das Parent-Element kein v-model binding macht ... sonst wird die Änderung nicht erkannt!
            valueHasChanged() {
                return (this.value !== this.currentValue);
            }
        },

        components: {
            textFieldIcon,
            bButton
        }
    }
</script>

<style type="scss">
    @import "generalCss";


</style>