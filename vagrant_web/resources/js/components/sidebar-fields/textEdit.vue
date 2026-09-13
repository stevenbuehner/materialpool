<template>
  <div class="sideBarField textEditSidebarField">

    <div class="label">
      <slot name="label">
        <slot name="icon">
          <text-field-icon/>
        </slot>

        <span class="title">
          <slot name="title">{{ name }}</slot>
        </span>
      </slot>

      <div class="buttons">
        <b-button
            v-if="valueHasChanged && enableSaveButton && !disabled"
            size="sm"
            class="cancel-button"
            variant="danger"
            @click="cancelAndResetValue">
          <slot name="save-button">{{ $t('pool.cancel') }}</slot>
        </b-button>

        <b-button
            v-if="valueHasChanged && enableSaveButton && !disabled"
            size="sm"
            class="save-button"
            @click="sendSaveRequest">
          <slot name="save-button">{{ $t('pool.save') }}</slot>
        </b-button>
      </div>

    </div>

    <div class="editField"
         :class="{disabled}">

      <div class="inputWrapper">

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
              autocorrect="off"
          />

          <datepicker v-if="type === 'date'"
                      :disabled="disabled"
                      :typeable="true"
                      :model-value="currentValueInDayJsFormat"
                      :disabled-dates="{from: new Date()}"
                      :required="required"
                      :input-class="{valueChanged : valueHasChanged}"
                      :placeholder="getPlaceholder"
                      @update:model-value="onDateInputChanged"
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

  </div>
</template>

<script>
import {BButton, BFormInput, BFormTextarea}           from '@/adapters/bootstrap';
import textFieldIcon                                  from '@icons/vendor/svg-icon/svg/material/text-fields.svg'
import generalMixin                                   from './generalSidebarFields.mixin';
import Datepicker                                     from '../datepicker/datepicker';
import {server_datetime_format}                       from "../../apps/config";
import {moment}                                       from "../../apps/main/localisation";

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
      this.currentValue = currentValue || null;

      if (this.valueHasChanged) {
        this.$emit('input', this.cleanedValue);
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
        this.currentValue = moment(dateOrNullObject).format(server_datetime_format);
      }

      if (this.valueHasChanged) {
        this.$emit('input', this.cleanedValue);
      }
    },

    onEnter() {
      this.$emit('on-enter', this.cleanedValue);
      this.sendSaveRequest();
    },

    sendSaveRequest() {
      if (this.valueHasChanged) {
        this.$emit('save-request', this.cleanedValue);
      }
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
      return this.value !== this.cleanedValue;
    },

    cleanedValue() {
      if (typeof this.currentValue === 'string') {
        return this.currentValue.trim();
      } else {
        return this.currentValue;
      }
    },

    currentValueInDayJsFormat() {
      return moment(this.currentValue).toDate();
    }
  },

  components: {
    textFieldIcon,
    BButton,
    BFormInput,
    BFormTextarea,
    Datepicker
  }
}
</script>
