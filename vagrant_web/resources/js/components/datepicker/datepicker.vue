<script>

import ExtDatePicker                    from "@hokify/vuejs-datepicker";
import {getLocale, getLocaleDateFormat} from "../../apps/main/localisation";

export default {
  name: "datepicker",
  extends: ExtDatePicker,

  // Overwrite some defaults
  props: {
    format: {
      type: [String, Function],
      default: getLocaleDateFormat()
    },
    language: {
      type: String,
      default: getLocale()
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
@import "~@hokify/vuejs-datepicker/dist/vuejs-datepicker.css";
/* purgecss end ignore */
</style>