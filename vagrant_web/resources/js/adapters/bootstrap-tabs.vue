<template>
  <div v-bind="$attrs" class="tabs">
    <div :class="{'card-header': card}">
      <ul class="nav nav-tabs" :class="[navClass, {'card-header-tabs': card, 'nav-fill': fill, 'nav-justified': justified, small}]" role="tablist">
        <li v-for="(tab, index) in tabs" :key="tab.uid" class="nav-item" role="presentation">
          <a
              href="#"
              ref="tabLinks"
              class="nav-link"
              :class="{active: index === activeIndex, disabled: tab.disabled}"
              role="tab"
              :aria-selected="index === activeIndex ? 'true' : 'false'"
              :aria-disabled="tab.disabled ? 'true' : null"
              :tabindex="index === activeIndex ? null : -1"
              @click.prevent="activate(index)"
              @keydown="onKeydown($event, index)"
          >{{ tab.title }}</a>
        </li>
      </ul>
    </div>
    <div class="tab-content" :class="contentClass">
      <slot/>
    </div>
  </div>
</template>

<script>
import {adjacentEnabledTabIndex, enabledTabIndex, normalizeTabIndex} from './bootstrap-tabs';

export default {
    name: 'BTabs',
    inheritAttrs: false,
    props: {
        card: {type: Boolean, default: false},
        contentClass: {type: [String, Array, Object], default: null},
        fill: {type: Boolean, default: false},
        justified: {type: Boolean, default: false},
        modelValue: {default: undefined},
        navClass: {type: [String, Array, Object], default: null},
        small: {type: Boolean, default: false},
        value: {type: [Number, String], default: 0},
    },
    emits: ['activate-tab', 'changed', 'input', 'update:modelValue'],
    data() {
        return {tabs: [], localIndex: this.normalizeIndex(this.modelValue === undefined ? this.value : this.modelValue)};
    },
    provide() {
        return {bootstrapTabs: this};
    },
    computed: {
        activeIndex() {
            return enabledTabIndex(this.tabs, this.localIndex);
        },
    },
    watch: {
        modelValue(value) {
            if (value !== undefined) this.localIndex = this.normalizeIndex(value);
        },
        value(value) {
            if (this.modelValue === undefined) this.localIndex = this.normalizeIndex(value);
        },
    },
    methods: {
        normalizeIndex(value) {
            return normalizeTabIndex(value);
        },
        register(tab) {
            this.tabs.push(tab);
            this.$emit('changed', this.tabs);
        },
        unregister(tab) {
            this.tabs = this.tabs.filter(candidate => candidate !== tab);
            this.$emit('changed', this.tabs);
        },
        activate(index) {
            const tab = this.tabs[index];
            if (!tab || tab.disabled || index === this.activeIndex) return;
            const previous = this.activeIndex;
            this.localIndex = index;
            this.$emit('activate-tab', index, previous);
            this.$emit('input', index);
            this.$emit('update:modelValue', index);
        },
        onKeydown(event, index) {
            const directions = {
                ArrowLeft: 'previous',
                ArrowUp: 'previous',
                ArrowRight: 'next',
                ArrowDown: 'next',
                Home: 'first',
                End: 'last',
            };
            const direction = directions[event.key];
            if (!direction) return;

            event.preventDefault();
            const targetIndex = adjacentEnabledTabIndex(this.tabs, index, direction);
            if (targetIndex < 0) return;
            this.activate(targetIndex);
            this.$nextTick(() => this.$refs.tabLinks?.[targetIndex]?.focus());
        },
    },
};
</script>
