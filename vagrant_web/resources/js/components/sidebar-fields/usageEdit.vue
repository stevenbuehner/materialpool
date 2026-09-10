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
import {moment}         from "../../apps/main/localisation";

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
      const orderedUsages = this.usages.sort((e1, e2) => {
        return moment(e1.datetime).unix() - moment(e2.datetime).unix();
      });

      let getElementsCount = this.displayMax;
      let resultElements   = [];
      let foundElementId   = null;

      if (this.usages.length > this.displayMax) {

        // 1) find selected Element
        if (this.currentActiveUsageId !== null) {
          const foundElement = this.usages.find((el) => el.id === this.currentActiveUsageId);

          if (foundElement !== undefined) {
            resultElements.push(foundElement)
            foundElementId = foundElement.id;
            getElementsCount--;
          }
        }

        // 2) Fill with the last x entries
        for (let i = orderedUsages.length - 1; i >= 0 && getElementsCount > 0; i--) {
          const el = orderedUsages[i];

          if (el?.id !== foundElementId) {
            resultElements.push(el);
            getElementsCount--;
          }
        }

        return resultElements.sort((e1, e2) => {
          return moment(e1.datetime).unix() - moment(e2.datetime).unix();
        });

      } else {
        return orderedUsages;
      }

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
        return this.$store
                   .dispatch('materialusages/getMaterialUsages', this.materialId)
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

      const loggedInUserId = await this.$store.dispatch('general/currentUserId');

      this.$store
          .dispatch('materialusages/addMaterialUsage', {
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