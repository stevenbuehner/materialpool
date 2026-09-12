<template>
  <div class="sideBarField usageSidebarField">

    <div class="label">
      <slot name="label">
        <slot name="icon">
          <occasion-icon/>
        </slot>

        <span class="title">
          <slot name="title">{{ $tc('pool.Occasion', usagesCount, {count: usagesCount}) }}</slot>
        </span>
      </slot>

    </div>

    <div class="usageField"
         :class="{disabled}">

      <slot name="input">
        <ul :class="{isEmpty: listIsEmpty, disabled}" class="p-2" ref="usagelist">
          <li v-if="hiddenUsagesCount > 0" class="displayHidden"
              @click="displayMax = usages.length">
            >> {{ $tc('pool.Show-hidden-usages', hiddenUsagesCount, {count: hiddenUsagesCount}) }}
          </li>
          <usage-list-element
              v-for="el in displayedList"
              :datetime="el.datetime"
              :material_id="el.material_id"
              :id="el.id"
              :place="el.place"
              :reason="el.reason"
              :used_by="el.used_by"
              :key="el.id"
              :disabled="disabled"
              @element-clicked="editModeChanged"
              @input:saved="$asyncComputed.usages.update()"
              @input:removed="$asyncComputed.usages.update()"
              :current-active-usage-id="currentActiveUsageId"
          />
          <li v-if="!disabled" class="emptyPlaceholder">
            <b-button variant="outline-secondary" size="sm"
                      :title="$tc('pool.Add-usage')"
                      @click.prevent="addUsageClick">
              +
            </b-button>
          </li>
        </ul>
      </slot>

    </div>

  </div>
</template>

<script>
import usageListElement from "./usage/usageListElement";
import OccasionIcon     from '@icons/vendor/svg-icon/svg/icomoon/bubble2.svg';
import {BButton}        from '@/adapters/bootstrap';
import {savingDialogs}  from "../../helper/flashMessages";
import {useMaterialUsagesStore} from '../../apps/main/stores/materialUsages';
import {useGeneralStore}        from '../../apps/main/stores/general';
import {displayedUsages}        from './usage/usageHelpers';

export default {
  name: "usageEdit",
  mixins: [savingDialogs],

  props: {
    disabled: {
      type: Boolean,
      required: false,
      default: false
    },

    materialId: {
      type: Number,
      required: true,
    }
  },

  data() {
    return {
      currentActiveUsageId: null,
      displayMax: 5
    };
  },


  computed: {
    listIsEmpty() {
      return this.usages.length === 0;
    },

    displayedList() {
      return displayedUsages(this.usages, this.displayMax, this.currentActiveUsageId);
    },

    usagesCount() {
      return this.usages?.length;
    },

    hiddenUsagesCount() {
      return this.usages.length - this.displayedList?.length;
    }

  },

  asyncComputed: {
    usages: {
      get() {
        return useMaterialUsagesStore()
                   .getMaterialUsages(this.materialId)
                   .then((d) => {
                     // Nur dann bekommt das Child-Listen-Element die Änderungen im Objekt mitgeteilt
                     this.$forceUpdate();
                     return d;
                   })
                   .catch((message) => {
                     this.flashError(this.$t('pool.Usage'), this.$t('pool.Could-not-load-Material-Usages'));
                   });
      },
      default() {
        return [];
      }
    }
  },

  watch: {},

  methods: {

    async addUsageClick() {

      const message = this.flashActionStartedWaiting(this.$t('pool.Adding-Usage'));

      const loggedInUserId = await useGeneralStore().currentUserId();

      useMaterialUsagesStore()
          .addMaterialUsage({
            material_id: this.materialId,
            used_by_id: loggedInUserId || null
          })
          .then((usage) => {
            this.flashActionSuccessfullyFinished(this.$t('pool.Usage-added'), message);
            this.currentActiveUsageId = usage.id; // Open the new Usage-ID and make it active
            this.$asyncComputed.usages.update(); // Force the async property usages to update from vuejs store
            return usage;
          })
          .catch((message) => {
            this.flashError(this.$t('pool.Usage'), this.$t('pool.Error-while-creating-usage'), message);
          });

    },

    editModeChanged(data) {
      this.currentActiveUsageId = data;
    }

  },

  components: {
    usageListElement,
    OccasionIcon,
    BButton
  }
}
</script>

<style lang="scss">
@import "resources/sass/theme";

.usageSidebarField {
  .label svg {
    width: .9em !important;
  }

  .usageField {

    > ul {
      // padding: 0;
      margin: 0;
      list-style-type: none;
      // background-color: $sidebar-input-background-colour-disabled;
      background-color: $sidebar-input-background-colour-active;
      border: $input-border-color solid 1px;
      border-radius: $input-border-radius;
      padding: $input-padding-y $input-padding-x;

      .displayHidden {
        cursor: pointer;
        font-style: italic;
      }

      .emptyPlaceholder {
        // height: $input-height-inner;
        margin: $input-padding-y 0;
        text-align: right;
      }

      &.isEmpty {
      }

      &.disabled {
        background-color: $sidebar-input-background-colour-disabled;
      }
    }
  }

}
</style>
