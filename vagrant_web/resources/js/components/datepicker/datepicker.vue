<template>
  <div :class="wrapperClass">
    <ext-date-picker
        :model-value="modelValue"
        :disabled="disabled"
        :formats="{input: format}"
        :input-attrs="inputAttributes"
        :locale="language"
        :max-date="disabledDates?.from"
        :min-date="disabledDates?.to"
        :placeholder="placeholder"
        :text-input="textInputConfiguration"
        :time-config="{enableTimePicker: false}"
        :ui="{input: normalizedInputClasses}"
        :week-start="mondayFirst ? 1 : 0"
        auto-apply
        @update:model-value="$emit('update:modelValue', $event)"
    />
  </div>
</template>

<script>
import ExtDatePicker                    from '@/adapters/datepicker';
import {getLocale, getLocaleDateFormat} from '../../apps/main/localisation';
import de                               from 'date-fns/locale/de';
import enUS                             from 'date-fns/locale/en-US';

import '@vuepic/vue-datepicker/dist/main.css';

const datepickerLocales = {de, en: enUS};

function getDatepickerFormat() {
  return getLocaleDateFormat()
      .replace('DD', 'dd')
      .replace('YYYY', 'yyyy');
}

function normalizeClasses(value) {
  if (typeof value === 'string') {
    return [value];
  }

  if (Array.isArray(value)) {
    return value.flatMap(normalizeClasses);
  }

  if (value && typeof value === 'object') {
    return Object.keys(value).filter(className => value[className]);
  }

  return [];
}

export default {
  name: 'datepicker',
  components: {ExtDatePicker},
  emits: ['update:modelValue'],

  props: {
    modelValue: {
      type: [Date, String],
      default: null
    },
    disabled: {
      type: Boolean,
      default: false
    },
    disabledDates: {
      type: Object,
      default: () => ({})
    },
    format: {
      type: [String, Function],
      default: getDatepickerFormat
    },
    language: {
      type: Object,
      default: () => datepickerLocales[getLocale()] || enUS
    },
    mondayFirst: {
      type: Boolean,
      default: true
    },
    placeholder: {
      type: String,
      default: ''
    },
    required: {
      type: Boolean,
      default: false
    },
    typeable: {
      type: Boolean,
      default: false
    },
    inputClass: {
      type: [String, Object, Array],
      default: ''
    },
    wrapperClass: {
      type: [String, Object, Array],
      default: 'dateInput'
    }
  },

  computed: {
    inputAttributes() {
      return {
        autocomplete: 'off',
        clearable: false,
        hideInputIcon: true,
        required: this.required
      };
    },

    normalizedInputClasses() {
      return normalizeClasses(this.inputClass);
    },

    textInputConfiguration() {
      if (!this.typeable) {
        return false;
      }

      if (typeof this.format !== 'string') {
        return true;
      }

      return {
        enterSubmit: true,
        format: this.format
      };
    }
  }
};
</script>
