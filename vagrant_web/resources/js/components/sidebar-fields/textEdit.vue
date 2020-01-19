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

            <div class="buttons">
                <b-button
                        v-if="valueHasChanged && enableSaveButton"
                        size="sm"
                        class="cancel-button"
                        variant="danger"
                        @click="cancelAndResetValue">
                    <slot name="save-button">{{$t('pool.cancel')}}</slot>
                </b-button>

                <b-button
                        v-if="valueHasChanged && enableSaveButton"
                        size="sm"
                        class="save-button"
                        @click="sendSaveRequest">
                    <slot name="save-button">{{$t('pool.save')}}</slot>
                </b-button>
            </div>

        </div>

        <div class="editField"
             :class="{disabled}">

            <slot name="input">
                <b-form-input
                        v-if="type === 'text'"
                        :class="[{valueChanged : valueHasChanged}, 'textInput']"
                        @input="onInputChanged"
                        @keyup.enter="onEnter"
                        @keyup.esc="cancelAndResetValue"
                        :value="currentValue"
                        :type="type"
                        :placeholder="getPlaceholder"
                        :disabled="disabled"
                        size="sm"
                        ref="input_field"
                />

                <datepicker v-if="type === 'date'"
                            class="dateInput"
                            :disabled="disabled"
                            :typeable="false"
                            :bootstrap-styling="true"
                            :language="dateLocalisation"
                            :value="currentValueInDayJsFormat"
                            :key="value"
                            :format="dateFormat"
                            :monday-first="true"
                            :disabled-dates="{from: new Date()}"
                            :required="required"
                            :input-class="{valueChanged : valueHasChanged}"
                            :placeholder="getPlaceholder"
                            @input="onDateInputChanged"
                />

                <b-form-textarea
                        v-if="type === 'textarea'"
                        :class="[{valueChanged : valueHasChanged}, 'textareaInput']"
                        :placeholder="getPlaceholder"
                        :rows="rows"
                        :value="currentValue"
                        :max-rows="8"
                        :disabled="disabled"
                        :style="{overflowY : disabled ? 'hidden' : 'scroll'}"
                        @input="onInputChanged"
                        @keyup.enter="onEnter"
                        @keyup.esc="cancelAndResetValue"
                        ref="input_field"
                />

            </slot>

        </div>

    </div>
</template>

<script>
	import Vue                                   from 'vue';
	import {FormInputPlugin, FormTextareaPlugin} from 'bootstrap-vue';
	import textFieldIcon                         from 'svg-icon/dist/svg/material/text-fields.svg'
	import generalMixin                          from './generalSidebarFields.mixin';
	import {BButton}                             from 'bootstrap-vue';
	import Datepicker                            from 'vuejs-datepicker';
	import {localisation, lang}                  from "../../apps/main/localisation";
	import dayjs                                 from 'dayjs';
	import {server_datetime_format}              from "../../apps/config";

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
				currentValue: '',
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
			/**
			 * @param {null|String} currentValue
			 * @returns {null|String}
			 */
			onInputChanged(currentValue) {
				this.currentValue = currentValue.trim() || null;

				if (this.valueHasChanged) {
					this.$emit('input', this.currentValue);
				}
			},

			/**
			 *
			 * @param {null|Date} dateOrNullObject
			 * @returns {null|String}
			 */
			onDateInputChanged(dateOrNullObject) {
				if (dateOrNullObject === null) {
					this.currentValue = null;
				} else {
					this.currentValue = dayjs(dateOrNullObject).format(server_datetime_format);
				}

				if (this.valueHasChanged) {
					this.$emit('input', this.currentValue);
				}
			},

			onEnter(event) {
				this.$emit('on-enter', this.currentValue);
				this.sendSaveRequest();
			},

			sendSaveRequest() {
				this.$emit('save-request', this.currentValue);
			},

			cancelAndResetValue() {
				// Reset
				this.currentValue = this.value;
				this.$emit('canceled', this.value);
			}


		},

		computed: {
			// Das funktioniert nur, wenn das Parent-Element kein v-model binding macht ... sonst wird die Änderung nicht erkannt!
			valueHasChanged() {
				return (this.value !== this.currentValue);
			},

			dateLocalisation() {
				return localisation[lang].datepicker;
			},

			dateFormat() {
				return localisation[lang].dateDisplayFormat;
			},

			currentValueInDayJsFormat() {
				return dayjs(this.value).toDate();
			}
		},

		components: {
			textFieldIcon,
			BButton,
			Datepicker
		}
	}
</script>

<style type="scss">
    //  @import '~vue-date-pick/src/vueDatePick.scss';

</style>