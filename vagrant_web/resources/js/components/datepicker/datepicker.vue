<script>

import ExtDatePicker                    from "@/adapters/datepicker";
import {getLocale, getLocaleDateFormat} from "../../apps/main/localisation";
import de                               from 'date-fns/locale/de';
import enUS                             from 'date-fns/locale/en-US';

const datepickerLocales = {de, en: enUS};

function getDatepickerFormat() {
  return getLocaleDateFormat()
      .replace('DD', 'dd')
      .replace('YYYY', 'yyyy');
}

export default {
  name: "datepicker",
  extends: ExtDatePicker,

  // Overwrite some defaults
  props: {
    format: {
      type: [String, Function],
      default: getDatepickerFormat()
    },
    language: {
      type: Object,
      default: () => datepickerLocales[getLocale()] || enUS
    },
    mondayFirst: {
      type: Boolean,
      default: true
    },
    bootstrapStyling: {
      type: Boolean,
      default: true
    },
    wrapperClass: {
      type: [String, Object, Array],
      default: 'dateInput'
    },
  }
}
</script>

<style type="scss">
/* purgecss start ignore */
@import "~@wslyhbb/vuejs-datepicker/dist/vuejs-datepicker.css";
/* purgecss end ignore */
</style>
