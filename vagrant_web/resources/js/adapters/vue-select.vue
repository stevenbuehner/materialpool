<template>
  <Multiselect
      ref="multiselect"
      :class="{
        'vs--single': !multiple,
        'vs--multiple': multiple,
        'vs--searchable': true,
        'vs--loading': loading,
      }"
      :model-value="normalizedValue"
      :options="normalizedOptions"
      :mode="multiple ? 'tags' : 'single'"
      :object="true"
      :allow-absent="true"
      :label="LABEL_KEY"
      :value-prop="VALUE_KEY"
      :track-by="LABEL_KEY"
      :searchable="true"
      :filter-results="filterable"
      :clear-on-select="clearSearchOnSelect"
      :close-on-select="closeOnSelect"
      :can-clear="!multiple"
      :can-deselect="false"
      :hide-selected="false"
      :disabled="disabled"
      :loading="loading"
      :placeholder="placeholder"
      :classes="compatibilityClasses"
      :attrs="searchAttributes"
      @update:model-value="onUpdate"
      @search-change="onSearch"
      @open="$emit('open')"
      @close="$emit('close')"
      @keydown="onKeydown"
  >
    <template #option="{option}">
      <slot name="option" v-bind="slotBindings(option)">
        {{ option[LABEL_KEY] }}
      </slot>
    </template>

    <template #singlelabel="{value}">
      <slot name="selected-option" v-bind="slotBindings(value)">
        {{ value[LABEL_KEY] }}
      </slot>
    </template>

    <template #tag="{option, disabled: optionDisabled}">
      <slot
          name="selected-option-container"
          :option="unwrap(option)"
          :disabled="optionDisabled"
          :multiple="multiple"
          :deselect="deselect"
      >
        <span class="vs__selected">
          <slot name="selected-option" v-bind="slotBindings(option)">
            {{ option[LABEL_KEY] }}
          </slot>
          <button
              v-if="!optionDisabled"
              type="button"
              class="vs__deselect"
              :aria-label="`Deselect ${option[LABEL_KEY]}`"
              @click.stop="deselect(option)"
          >
            <span aria-hidden="true">&times;</span>
          </button>
        </span>
      </slot>
    </template>

    <template #nooptions>
      <slot name="no-options" v-bind="emptySlotBindings"/>
    </template>

    <template #noresults>
      <slot name="no-options" v-bind="emptySlotBindings"/>
    </template>

    <template #afterlist="{options: filteredOptions}">
      <slot
          name="list-footer"
          :search="searchTerm"
          :loading="setLoading"
          :searching="Boolean(searchTerm)"
          :filtered-options="filteredOptions.map(unwrap)"
      />
    </template>
  </Multiselect>
</template>

<script>
import Multiselect                            from '@vueform/multiselect';
import {stableOptionKey, unwrapSelectOption} from './vue-select-normalize';

const LABEL_KEY = '__materialpoolLabel';
const VALUE_KEY = '__materialpoolKey';

export default {
  name: 'MaterialpoolVueSelect',
  components: {Multiselect},
  emits: ['input', 'update:modelValue', 'search', 'search:focus', 'search:blur', 'open', 'close'],
  props: {
    value: {
      default: undefined,
    },
    modelValue: {
      default: undefined,
    },
    options: {
      type: Array,
      default: () => [],
    },
    multiple: {
      type: Boolean,
      default: false,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    filterable: {
      type: Boolean,
      default: true,
    },
    clearSearchOnSelect: {
      type: Boolean,
      default: true,
    },
    closeOnSelect: {
      type: Boolean,
      default: true,
    },
    getOptionLabel: {
      type: Function,
      default: null,
    },
    getOptionKey: {
      type: Function,
      default: null,
    },
    label: {
      type: String,
      default: 'label',
    },
    selectOnTab: {
      type: Boolean,
      default: false,
    },
    placeholder: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      LABEL_KEY,
      VALUE_KEY,
      loading: false,
      searchTerm: '',
      searchInput: null,
      searchAttributes: {
        autocorrect: 'off',
        autocapitalize: 'off',
        spellcheck: 'false',
      },
      compatibilityClasses: {
        container: 'multiselect v-select',
        containerDisabled: 'is-disabled vs--disabled',
        containerOpen: 'is-open vs--open',
        containerActive: 'is-active',
        wrapper: 'multiselect-wrapper vs__dropdown-toggle',
        singleLabel: 'multiselect-single-label vs__selected-options',
        singleLabelText: 'multiselect-single-label-text',
        search: 'multiselect-search vs__search',
        tags: 'multiselect-tags vs__selected-options',
        tagsSearch: 'multiselect-tags-search vs__search',
        placeholder: 'multiselect-placeholder vs__selected vs__placeholder',
        caret: 'multiselect-caret vs__open-indicator',
        clear: 'multiselect-clear vs__clear',
        spinner: 'multiselect-spinner vs__spinner',
        dropdown: 'multiselect-dropdown',
        dropdownHidden: 'is-hidden',
        options: 'multiselect-options vs__dropdown-menu',
        option: 'multiselect-option vs__dropdown-option',
        optionPointed: 'is-pointed vs__dropdown-option--highlight',
        optionSelected: 'is-selected vs__dropdown-option--selected',
        optionDisabled: 'is-disabled vs__dropdown-option--disabled',
        optionSelectedPointed: 'is-selected is-pointed vs__dropdown-option--selected vs__dropdown-option--highlight',
        noOptions: 'multiselect-no-options vs__no-options',
        noResults: 'multiselect-no-results vs__no-options',
      },
    };
  },
  computed: {
    currentValue() {
      return this.modelValue !== undefined ? this.modelValue : this.value;
    },
    normalizedOptions() {
      return this.options.map(this.normalize);
    },
    normalizedValue() {
      if (this.multiple) {
        return Array.isArray(this.currentValue) ? this.currentValue.map(this.normalize) : [];
      }

      return this.currentValue === null || this.currentValue === undefined
          ? null
          : this.normalize(this.currentValue);
    },
    emptySlotBindings() {
      return {
        search: this.searchTerm,
        loading: this.loading,
        searching: Boolean(this.searchTerm),
      };
    },
  },
  mounted() {
    this.connectSearchEvents();
  },
  updated() {
    this.connectSearchEvents();
  },
  beforeUnmount() {
    this.disconnectSearchEvents();
  },
  methods: {
    normalize(option) {
      return {
        [LABEL_KEY]: this.getOptionLabel ? this.getOptionLabel(option) : option?.[this.label] ?? option,
        [VALUE_KEY]: String(this.getOptionKey ? this.getOptionKey(option) : stableOptionKey(option)),
        __materialpoolOption: option,
      };
    },
    unwrap: unwrapSelectOption,
    slotBindings(option) {
      const original = this.unwrap(option);
      return original && typeof original === 'object' ? original : {label: original};
    },
    onUpdate(value) {
      const unwrapped = Array.isArray(value) ? value.map(this.unwrap) : this.unwrap(value);
      this.$emit('input', unwrapped);
      this.$emit('update:modelValue', unwrapped);
    },
    onSearch(query) {
      this.searchTerm = query || '';
      this.$emit('search', this.searchTerm, this.setLoading);
    },
    setLoading(loading) {
      this.loading = Boolean(loading);
    },
    onKeydown(event, multiselect) {
      if (this.selectOnTab && event.key === 'Tab') {
        const option = multiselect.pointer
            || multiselect.filteredOptions?.find(candidate => !multiselect.isSelected(candidate));

        if (option) {
          multiselect.handleOptionClick(option);
        }
      }
    },
    focusSearch() {
      this.$refs.multiselect?.input?.focus();
    },
    isOptionSelected(option) {
      return this.$refs.multiselect?.isSelected(this.normalize(option)) || false;
    },
    select(option) {
      this.$refs.multiselect?.select(this.normalize(option));
    },
    deselect(option) {
      this.$refs.multiselect?.deselect(this.normalize(this.unwrap(option)));
    },
    connectSearchEvents() {
      const input = this.$refs.multiselect?.input;
      if (input === this.searchInput) {
        return;
      }

      this.disconnectSearchEvents();
      this.searchInput = input || null;
      this.searchInput?.addEventListener('focus', this.onSearchFocus);
      this.searchInput?.addEventListener('blur', this.onSearchBlur);
    },
    disconnectSearchEvents() {
      this.searchInput?.removeEventListener('focus', this.onSearchFocus);
      this.searchInput?.removeEventListener('blur', this.onSearchBlur);
      this.searchInput = null;
    },
    onSearchFocus() {
      this.$emit('search:focus');
    },
    onSearchBlur() {
      this.$emit('search:blur');
    },
  },
};
</script>

<style src="@vueform/multiselect/themes/default.css"></style>
<style src="../legacy-ports/vue-select-3.20.4/vue-select.css"></style>
