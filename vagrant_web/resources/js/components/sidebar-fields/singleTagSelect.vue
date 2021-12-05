<template>
  <div class="sideBarField singleTagEditSidebarField">

    <div class="label">
      <slot name="label">
        <slot name="icon">
          <tag-icon/>
        </slot>

        <span class="title">
                    <slot name="title">{{ name }}</slot>
                </span>
      </slot>
    </div>


    <div :class="[{disabled, notDisabled : !disabled}, {isEmpty:!value}]"
         class="editField">

      <vue-select
          v-model="selection"
          :disabled="disabled"
          :getOptionLabel="getTagLabelFromObject"
          :multiple="false"
          :options="suggestedTags"
          :placeholder="placeholder"
          language="de-DE"
          @input="onInputChanged"
          @search="onSearch"
      >

        <template v-slot:selected-option="option">
          <div class="selected d-center">
            {{ getTagLabelFromObject(option) }}
          </div>
        </template>

        <template v-slot:option="option">
          <span :class="{'is-new' : option.isNew}" class="suggested-option">
              <span class="tagOptionIcon">
                  <slot name="icon">
                      <span :style="{backgroundImage: 'url(' + option.icon + ')'}" class="icon"/>
                  </slot>
              </span>
              <span class="suggested-text">
                  {{ getTagLabelFromObject(option) }}
              </span>
              <span v-if="option.isNew" class="is-new badge badge-info">{{ $t('pool.new') }}</span>
          </span>
        </template>

        <template v-slot:no-options>{{ $t('pool.Nothing-found') }}</template>

      </vue-select>

    </div>
  </div>

</template>

<script>

import generalMixin    from './generalSidebarFields.mixin';
import VueSelect       from 'vue-select/dist/vue-select';
import _debounce       from 'lodash/debounce';
import tagIcon         from 'svg-icon/dist/svg/material/style.svg';
import {savingDialogs} from "../../helper/flashMessages";


export default {

  name: "singleTagSelect",

  mixins: [generalMixin, savingDialogs],

  props: {
    filterType: {
      type: String,
      required: false,
      default: 'person'
    },
  },

  beforeUpdate() {

    if (this.value !== null && typeof this.value !== 'object') {
      console.error('Property value need to be null ob typeof object.');
    }

  },

  data() {
    return {
      suggestedTags: [],
      selection: this.value,
    }
  },

  methods: {
    getTagLabelFromObject(value) {
      if (typeof value === 'object') {
        if (!value.hasOwnProperty('title')) {
          return console.warn(
              `[vue-select warn]: Label key "option.title" does not` +
              ` exist in options object ${JSON.stringify(value)}.\n` +
              'http://sagalbot.github.io/vue-select/#ex-labels'
          )
        } else {
          return value.title;
        }

      } else {
        return value;
      }
    },

    onSearch(search, loading) {
      const type = this.filterType || false;

      loading(true);
      this.search(loading, search, type, this);
    },

    search: _debounce((loading, search, type, vm) => {

      vm.$store.dispatch('keywords/search', {searchText: search, type})
        .then((keywords) => {

          if (search.length > 2) {
            keywords.push({
              title: search,
              type: type,
              isNew: true
            });
          }

          vm.suggestedTags = keywords;
          loading(false);
        });

    }, 250),

    onInputChanged(input) {

      // Nothing to change
      if ((input === null && this.value === null) || (input && this.value && input.id === this.value.id)) {
        return;
      }

      if (input === null) {
        this.emitKeywordDissociated();
      } else if (input instanceof Object && input.id) {
        this.emitKeywordAssociated(input);
      } else if (input && input.new === true) {

        const statusFlash = this.flashStartSaving(this.$t('pool.new-keyword') + ' ' + input.title)

        // Keyword first has to be created first
        this.$store.dispatch('keywords/create', {
          title: input.title,
          type: this.filterType
        }).then((keyword) => {
          this.selection = keyword;
          this.flashSaved(this.$t('pool.keyword'));
          this.emitKeywordAssociated(keyword);
        }).catch((errorMessage) => {
          this.flashError(this.$t('pool.new-keyword') + ' ' + input.title, errorMessage);
        }).then(() => {
          statusFlash.destroy();
        });

      }
    },


    emitKeywordAssociated(newKeyword) {
      this.$emit('input:associated', newKeyword);
    },

    emitKeywordDissociated() {
      this.$emit('input:dissociated', null);
    }
  },

  components: {
    VueSelect,
    tagIcon
  },

}
</script>

<style lang="scss">
@import "resources/sass/theme";

.singleTagEditSidebarField {

  .notDisabled {
    .vs__dropdown-toggle {
      background-color: $sidebar-input-background-colour-active;
      padding: 0;

      .vs__selected-options {
        padding: 0;

        ::placeholder {
          color: $input-placeholder-color;
        }

        .vs__search {
          background-color: $sidebar-input-background-colour-active;
          padding: $input-padding-y-sm $input-padding-x-sm;
          margin: 0;
          line-height: $input-line-height-sm;
        }

        .vs__selected {
          padding: $input-padding-y-sm $input-padding-x-sm;
          // margin: 0;
        }
      }
    }
  }

  .vs--disabled {
    .vs__selected {
      color: $sidebar-input-font-color-disabled;
    }
  }

  .tagOptionIcon svg {
    height: 1em;
    width: 1em;
  }
}

</style>