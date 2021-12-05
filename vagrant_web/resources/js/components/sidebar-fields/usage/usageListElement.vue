<template>
  <li :class="{isActive : !disabled, isOpen: isElementSelectedForEdit, isClosed: !isElementSelectedForEdit}"
      class="usage-edit-list-el">
    <div class="wrapper">

      <template v-if="disabled || !isElementSelectedForEdit">
        <div class="list-mode" @click.stop="onClickListItem">
          <checkbox-icon class="checkbox"/>
          <span class="content">
            <span class="reason">{{ reason }}</span>
            <template v-if="place !== ''">
              (<span class="place">{{ place }}</span>, <span>{{ datetime | dateformat }}</span>)
            </template>
            <template v-if="place === ''">
              (<span>{{ datetime | dateformat }}</span>)
            </template>
          </span>

          <span @click.stop="onRequestDelete" :title="$t('pool.delete')" class="trash">
            <trash-icon class="trash-icon icon"/>
          </span>
        </div>
      </template>


      <template v-else>

        <span>
          <b-form-input
              ref="reason_input_field"
              :class="[{valueChanged : reasonChanged}, 'textInput']"
              :disabled="disabled"
              :placeholder="$t('pool.Reason')"
              v-model="modifiedData.reason"
              autocorrect="off"
              class="dateInput"
              size="sm"
              type="text"
              @keyup.enter="onRequestSave"
              @keyup.esc="onRequestCancel"
          />
        </span>

        <span>
          <b-form-input
              ref="place_input_field"
              :class="[{valueChanged : placeChanged}, 'textInput']"
              :disabled="disabled"
              :placeholder="$t('pool.Place')"
              v-model="modifiedData.place"
              autocorrect="off"
              size="sm"
              type="text"
              @keyup.enter="onRequestSave"
              @keyup.esc="onRequestCancel"
          />
        </span>

        <datepicker
            ref="datepicker"
            :disabled="disabled"
            :disabled-dates="disabledDates"
            :input-class="{valueChanged : dateTimeChanged}"
            :placeholder="$t('pool.Appointment')"
            :required="true"
            :typeable="true"
            v-model="modifiedData.datetime"
        />

        <span class="buttons">
          <span @click.stop="onRequestCancel" :title="$t('pool.Cancel')">
            <cancel-icon class="icon cancel-icon"/>
          </span>
          <span @click.stop="onRequestSave" :title="$t('pool.Save')" v-show="hasChanged">
            <check-circle-icon class="icon check-icon"/>
          </span>
        </span>

      </template>

    </div>
  </li>
</template>

<script>
import {getLocale, getLocaleDateFormat} from "../../../apps/main/localisation";
import checkboxIcon                     from 'svg-icon/dist/svg/ionic/android-checkbox-outline.svg';
import Datepicker                       from '../../datepicker/datepicker';
import {BButton}                        from 'bootstrap-vue';
import trashIcon                        from 'svg-icon/dist/svg/oct/trashcan.svg';
import moment                           from "moment";
import {savingDialogs}                  from "../../../helper/flashMessages";
import cancelIcon                       from 'svg-icon/dist/svg/material/cancel.svg';
import checkCircleIcon                  from 'svg-icon/dist/svg/material/check-circle.svg';

export default {
  name: "usageListElement",
  mixins: [savingDialogs],
  props: {

    id: {
      type: Number,
      required: true
    },

    material_id: {
      type: Number,
      required: true
    },

    datetime: {
      type: String,
      required: true
    },

    reason: {
      type: String,
      required: true
    },

    place: {
      type: String,
      required: true
    },

    used_by: {
      validator: (prop) => {
        return typeof (prop === 'object' && prop.hasOwnProperty('id'))
               || prop === null;
      },
      required: true
    },

    disabled: {
      type: Boolean,
      required: false,
      default: false,
    },

    currentActiveUsageId: {
      validator: prop => typeof prop === 'number' || prop === null,
      required: true
    }
  },

  watch: {
    currentActiveUsageId(value, valueOld) {
      // Beim Anklicken gleich in das Feld Grund springen
      if (valueOld !== this.id && value === this.id) {
        this.$nextTick(() => {
          this.$refs?.reason_input_field.$el.focus();
        });
      }
    },

    datetime() {
      this.init();
    }, reason() {
      this.init();
    }, place() {
      this.init();
    },
    used_by() {
      // Fixme: Deep Objekt wird vermutlich problematisch
      this.init();
    },

  },


  mounted() {
    this.init();
  },

  data() {
    return {
      modifiedData: {
        datetime: null,
        reason: null,
        place: null,
        used_by_id: null,
      },

      updateInProgress: false,
    };
  },

  computed: {

    isElementSelectedForEdit() {
      return this.id === this.currentActiveUsageId;
    },

    dateLocalisation() {
      return getLocale();
    },

    dateFormat() {
      return getLocaleDateFormat();
    },

    disabledDates() {
      // keine UsageDates eintragbar die weiter als 90 Tage ind er Zukunft liegen
      const today    = new Date();
      const oneMonth = new Date(today.getTime() + 90 * 24 * 60 * 60 * 1000)

      return {
        from: oneMonth
      }
    },

    dateTimeChanged() {
      return this.modifiedData.datetime?.getTime() !== new Date(this.datetime).getTime();
    },

    reasonChanged() {
      return this.modifiedData.reason !== this.reason;
    },

    placeChanged() {
      return this.modifiedData.place !== this.place;
    },

    usedByChanged() {
      return this.modifiedData.used_by_id !== this.used_by?.id;
    },

    hasChanged() {
      return this.dateTimeChanged || this.reasonChanged || this.placeChanged || this.usedByChanged;
    }

  },


  methods: {
    init() {
      // deep copy data
      this.modifiedData.datetime   = new Date(this.datetime);
      this.modifiedData.reason     = this.reason;
      this.modifiedData.place      = this.place;
      this.modifiedData.used_by_id = this.used_by?.id;
    },

    onClickListItem() {
      // Request parent to switch into edit-mode for this element and to close other elements
      this.$emit('element-clicked', this.id);
    },

    onRequestCancel() {

      if (this.isElementSelectedForEdit && this.hasChanged === true) {
        console.log('Request confirmation');

        if (confirm(this.$t('pool.You-have-unsaved-data.-Do-you-want-to-continue-anyways?')) === false) {
          return;
        }
      }

      this.$emit('element-clicked', null);

    },

    onRequestSave() {

      if (this.updateInProgress === true) {
        return;
      }

      this.updateInProgress = true;
      const msg             = this.flashActionStartedWaiting(this.$t('pool.Updating-usage'));

      // Close-Edit Mode
      this.$emit('element-clicked', null);

      this.$store
          .dispatch('materialusages/updateMaterialUsage', {
            material_id: this.material_id,
            id: this.id,
            datetime: this.modifiedData.datetime,
            place: this.modifiedData.place,
            reason: this.modifiedData.reason,
            used_by_id: this.modifiedData.used_by_id
          })
          .then((data) => {
            this.flashActionSuccessfullyFinished(this.$t('pool.Usage-stored'), msg);
            this.$emit('input:saved');
          })
          .catch((message) => {
            console.error(message);
            this.flashError(this.$t('pool.Usage'), this.$t('pool.Could-not-store-Material-Usage'), msg);
          })
          .then(() => {
            this.updateInProgress = false;
          });
    },

    onRequestDelete() {
      if (this.updateInProgress === true) {
        return;
      }

      // Nur dann nachfragen, wenn eines der Felder (Place oder Reason) auch ausgefüllt sind;
      const fieldsEmpty = this.place === '' && this.reason === '';

      if (fieldsEmpty || confirm(this.$t('pool.Do-you-realy-want-to-delete-this-entry?')) === true) {

        const msg             = this.flashStartRemoving(this.$t('pool.Usage'));
        this.updateInProgress = true;

        this.$store
            .dispatch('materialusages/deleteMaterialUsage', {
              material_id: this.material_id,
              id: this.id
            })
            .then((data) => {
              this.flashRemoved(this.$t('pool.Usage'), msg);
              this.$emit('input:removed');
            })
            .catch((message) => {
              console.error(message);
              this.flashError(this.$t('pool.Usage'), this.$t('pool.Could-not-remove-Material-Usage'), msg);
            })
            .then(() => {
              // Cleanup :-)
              this.updateInProgress = false;
            });

      }

    },

    onDatetimeChanged(value) {

    },

    onPlaceChanged(value) {

    },

    onReasonChanged(value) {

    },

  },

  filters: {
    dateformat(datetime) {
      return moment(datetime).format(getLocaleDateFormat());
    }
  },

  components: {
    Datepicker,
    checkboxIcon,
    trashIcon,
    cancelIcon,
    checkCircleIcon,
    BButton
  }

}
</script>

<style lang="scss">
@import "resources/sass/theme";

$button-padding-left: .5em;

li.usage-edit-list-el {

  margin: 0 -2px;
  padding: 0;

  > .wrapper {
    display: flex;

    .buttons {
      display: inline-flex;
      align-items: center;
      padding-left: $button-padding-left;

      > span {
        cursor: pointer;
      }
    }

    .icon {
      width: 1.25em;
      height: 1.25em;
    }

    > * {
      padding: 0 2px
    }

    .list-mode {
      display: flex;
      align-items: flex-start;
      margin: .5em 0;
      width: 100%;

      .checkbox {
        width: 1.5em;
        margin-right: $button-padding-left;
      }

      .reason {
        font-weight: bold;
      }

      .place {
      }

      .trash {
        padding-left: 1em;
        margin-left: auto;
      }
    }

  }

  &.isActive.isClosed {
    cursor: pointer;
  }


}
</style>