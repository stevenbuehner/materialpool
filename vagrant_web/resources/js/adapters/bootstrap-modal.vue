<template>
  <teleport to="body">
    <div
        v-bind="$attrs"
        v-if="localVisible"
        ref="modal"
        class="modal fade show d-block"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        @mousedown.self="onBackdrop"
        @keydown="onKeydown"
    >
      <div :class="dialogClasses" role="document">
        <div class="modal-content">
          <div :class="['modal-header', headerClass]">
            <slot name="modal-header" :close="close">
              <h5 :id="titleId" class="modal-title"><slot name="modal-title">{{ title }}</slot></h5>
              <button
                  v-if="!hideHeaderClose"
                  type="button"
                  class="close"
                  aria-label="Close"
                  :disabled="busy"
                  @click="close"
              ><span aria-hidden="true">&times;</span></button>
            </slot>
          </div>
          <div v-if="renderContent" class="modal-body"><slot/></div>
          <div v-if="!hideFooter" class="modal-footer">
            <slot name="modal-footer" :cancel="cancel" :close="close" :hide="hide" :ok="ok">
              <button type="button" class="btn btn-secondary" :disabled="busy" @click="cancel">Cancel</button>
              <button type="button" class="btn btn-primary" :disabled="busy" @click="ok">OK</button>
            </slot>
          </div>
        </div>
      </div>
    </div>
    <div v-if="localVisible" class="modal-backdrop fade show"/>
  </teleport>
</template>

<script>
import {modalDialogClasses} from './bootstrap-modal';

let modalUid = 0;
let openModalCount = 0;

export default {
    name: 'BModal',
    inheritAttrs: false,
    props: {
        busy: {type: Boolean, default: false},
        centered: {type: Boolean, default: false},
        dialogClass: {type: [String, Array, Object], default: null},
        headerClass: {type: [String, Array, Object], default: null},
        hideFooter: {type: Boolean, default: false},
        hideHeaderClose: {type: Boolean, default: false},
        lazy: {type: Boolean, default: false},
        modelValue: {type: Boolean, default: undefined},
        noCloseOnBackdrop: {type: Boolean, default: false},
        noCloseOnEsc: {type: Boolean, default: false},
        scrollable: {type: Boolean, default: false},
        size: {type: String, default: null},
        title: {type: String, default: ''},
        value: {type: Boolean, default: false},
    },
    emits: ['cancel', 'hide', 'hidden', 'input', 'ok', 'show', 'shown', 'update:modelValue'],
    data() {
        modalUid += 1;
        const visible = this.modelValue === undefined ? this.value : this.modelValue;
        return {
            hasShown: Boolean(visible),
            localVisible: Boolean(visible),
            returnFocusTo: null,
            titleId: `materialpool-modal-title-${modalUid}`,
        };
    },
    computed: {
        dialogClasses() {
            return modalDialogClasses(this);
        },
        is_visible() {
            return this.localVisible;
        },
        renderContent() {
            return !this.lazy || this.hasShown;
        },
    },
    watch: {
        modelValue(value) {
            if (value === undefined) return;
            value ? this.show() : this.hide();
        },
        value(value) {
            if (this.modelValue !== undefined) return;
            value ? this.show() : this.hide();
        },
    },
    mounted() {
        if (this.localVisible) this.opened();
    },
    beforeUnmount() {
        if (this.localVisible) this.releaseBody();
    },
    methods: {
        show(returnFocusTo = null) {
            if (this.localVisible) return;
            this.$emit('show');
            this.returnFocusTo = returnFocusTo || document.activeElement;
            this.localVisible = true;
            this.hasShown = true;
            this.emitModel(true);
            this.opened();
        },
        hide(trigger = null) {
            if (!this.localVisible) return;
            const event = this.modalEvent(trigger);
            this.$emit('hide', event);
            if (event.defaultPrevented) return;
            this.localVisible = false;
            this.emitModel(false);
            this.releaseBody();
            this.$nextTick(() => {
                this.$emit('hidden', event);
                this.returnFocusTo?.focus?.();
                this.returnFocusTo = null;
            });
        },
        close() {
            if (!this.busy) this.hide('headerclose');
        },
        cancel() {
            if (this.busy) return;
            const event = this.modalEvent('cancel');
            this.$emit('cancel', event);
            if (!event.defaultPrevented) this.hide('cancel');
        },
        ok() {
            if (this.busy) return;
            const event = this.modalEvent('ok');
            this.$emit('ok', event);
            if (!event.defaultPrevented) this.hide('ok');
        },
        onBackdrop() {
            if (!this.busy && !this.noCloseOnBackdrop) this.hide('backdrop');
        },
        onKeydown(event) {
            if (event.key === 'Escape' && !this.busy && !this.noCloseOnEsc) {
                event.preventDefault();
                this.hide('esc');
                return;
            }
            if (event.key !== 'Tab') return;
            const focusable = this.focusableElements();
            if (!focusable.length) {
                event.preventDefault();
                event.currentTarget.focus();
                return;
            }
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
        focusableElements() {
            const selector = 'a[href], button:not(:disabled), input:not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex]:not([tabindex="-1"])';
            return [...(this.$refs.modal?.querySelectorAll(selector) || [])].filter(element => !element.hidden);
        },
        opened() {
            openModalCount += 1;
            document.body.classList.add('modal-open');
            this.$nextTick(() => {
                const focusModal = () => {
                    if (!this.localVisible) return;
                    const focusable = this.focusableElements();
                    (focusable[0] || this.$refs.modal)?.focus?.();
                    this.$emit('shown');
                };

                if (typeof window.requestAnimationFrame === 'function') {
                    window.requestAnimationFrame(focusModal);
                } else {
                    focusModal();
                }
            });
        },
        releaseBody() {
            openModalCount = Math.max(0, openModalCount - 1);
            if (openModalCount === 0) document.body.classList.remove('modal-open');
        },
        emitModel(value) {
            this.$emit('input', value);
            this.$emit('update:modelValue', value);
        },
        modalEvent(trigger) {
            return {
                defaultPrevented: false,
                trigger,
                preventDefault() {
                    this.defaultPrevented = true;
                },
            };
        },
    },
};
</script>
