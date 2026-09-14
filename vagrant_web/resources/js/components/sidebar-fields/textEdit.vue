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

      <div class="buttons d-flex gap-1">
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
         :class="{disabled, valueChanged: valueHasChanged}">

      <div class="inputWrapper">

        <slot name="input">
          <b-form-input
              v-if="type === 'text'"
              :class="[{valueChanged : valueHasChanged, hasClearButton: clearable}, 'textInput']"
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

          <b-button
              v-if="type === 'text' && clearable && currentValue && !disabled"
              class="clear-button"
              size="sm"
              variant="link"
              :title="$t('pool.clear-field')"
              :aria-label="$t('pool.clear-field')"
              @click="clearAndFocusInput">
            <clear-icon class="clear-button-icon" aria-hidden="true"/>
          </b-button>

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
import clearIcon                                      from '../bible-popover/close.svg';
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

    clearable: {
      type: Boolean,
      required: false,
      default: false
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
    },

    clearAndFocusInput() {
      this.onInputChanged('');
      this.$nextTick(() => this.$refs.input_field.focus());
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
    clearIcon,
    BButton,
    BFormInput,
    BFormTextarea,
    Datepicker
  }
}
</script>

<style lang="scss" scoped>
.inputWrapper {
  position: relative;

  .textInput.hasClearButton {
    padding-right: 2rem;
  }

  .clear-button {
    align-items: center;
    background-color: transparent;
    border: 0;
    color: var(--bs-secondary-color);
    display: flex;
    height: 2rem;
    justify-content: center;
    padding: 0;
    position: absolute;
    right: 0;
    text-decoration: none;
    top: 50%;
    transform: translateY(-50%);
    width: 2rem;
    touch-action: manipulation;

    &::before {
      content: '';
      inset: -.375rem;
      position: absolute;
    }

    &:hover,
    &:focus-visible {
      background-color: transparent;
      color: var(--bs-body-color);
      text-decoration: none;
    }

    &:focus-visible {
      outline: var(--bs-focus-ring-width) solid var(--bs-focus-ring-color);
      outline-offset: -2px;
    }
  }

  .clear-button-icon {
    background-color: var(--bs-secondary-bg);
    border-radius: 50%;
    box-sizing: border-box;
    fill: currentColor;
    height: .875rem;
    padding: .1875rem;
    position: relative;
    width: .875rem;
  }
}
</style>
