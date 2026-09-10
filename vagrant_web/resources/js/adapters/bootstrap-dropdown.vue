<template>
  <component
      v-bind="$attrs"
      :is="nav ? 'li' : 'div'"
      ref="root"
      class="dropdown b-dropdown"
      :class="[nav ? 'nav-item b-nav-dropdown' : 'btn-group', {show: visible}]"
  >
    <component
        :is="nav ? 'a' : 'button'"
        ref="toggleButton"
        :href="nav ? '#' : null"
        :type="nav ? null : 'button'"
        class="dropdown-toggle"
        :class="nav ? 'nav-link' : ['btn', `btn-${variant}`, size ? `btn-${size}` : null]"
        :disabled="!nav && disabled"
        :aria-disabled="disabled ? 'true' : null"
        aria-haspopup="menu"
        :aria-expanded="visible ? 'true' : 'false'"
        @click.prevent="toggle"
        @keydown="onToggleKeydown"
    ><slot name="button-content">{{ text }}</slot></component>
    <ul
        v-show="visible"
        ref="menu"
        class="dropdown-menu"
        :class="{'dropdown-menu-end': right, show: visible}"
        role="menu"
        tabindex="-1"
        @keydown="onMenuKeydown"
    ><slot :hide="hide"/></ul>
  </component>
</template>

<script>
import {dropdownItemIndex} from './bootstrap-dropdown';

export default {
    name: 'BDropdown',
    inheritAttrs: false,
    props: {
        disabled: {type: Boolean, default: false},
        left: {type: Boolean, default: false},
        nav: {type: Boolean, default: false},
        right: {type: Boolean, default: false},
        size: {type: String, default: null},
        text: {type: String, default: ''},
        variant: {type: String, default: 'secondary'},
    },
    emits: ['hide', 'hidden', 'show', 'shown'],
    data() {
        return {visible: false};
    },
    provide() {
        return {bootstrapDropdown: this};
    },
    mounted() {
        document.addEventListener('click', this.onDocumentClick);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.onDocumentClick);
    },
    methods: {
        toggle() {
            if (this.disabled) return;
            this.visible ? this.hide() : this.show();
        },
        show() {
            if (this.disabled || this.visible) return;
            this.$emit('show');
            this.visible = true;
            this.$nextTick(() => this.$emit('shown'));
        },
        hide(returnFocus = false) {
            if (!this.visible) return;
            this.$emit('hide');
            this.visible = false;
            this.$nextTick(() => {
                this.$emit('hidden');
                if (returnFocus) this.$refs.toggleButton?.focus();
            });
        },
        onDocumentClick(event) {
            if (this.visible && !this.$refs.root?.contains(event.target)) this.hide();
        },
        onToggleKeydown(event) {
            if (!['Enter', ' ', 'ArrowDown'].includes(event.key)) return;
            event.preventDefault();
            this.show();
            this.$nextTick(() => this.focusMenuItem(0));
        },
        onMenuKeydown(event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                this.hide(true);
                return;
            }
            if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const items = this.menuItems();
            if (!items.length) return;
            const current = items.indexOf(document.activeElement);
            const index = dropdownItemIndex(items.length, current, event.key);
            items[index].focus();
        },
        menuItems() {
            return [...(this.$refs.menu?.querySelectorAll('.dropdown-item:not(.disabled):not(:disabled)') || [])];
        },
        focusMenuItem(index) {
            this.menuItems()[index]?.focus();
        },
    },
};
</script>
