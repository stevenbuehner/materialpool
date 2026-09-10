<template>
  <div
      v-show="isActive"
      v-bind="$attrs"
      class="tab-pane fade"
      :class="{active: isActive, show: isActive, 'card-body': bootstrapTabs && bootstrapTabs.card}"
      role="tabpanel"
      :aria-hidden="isActive ? 'false' : 'true'"
  ><slot/></div>
</template>

<script>
let tabUid = 0;

export default {
    name: 'BTab',
    inheritAttrs: false,
    inject: {bootstrapTabs: {default: null}},
    props: {
        active: {type: Boolean, default: false},
        disabled: {type: Boolean, default: false},
        title: {type: String, default: ''},
    },
    data() {
        tabUid += 1;
        return {uid: `materialpool-tab-${tabUid}`};
    },
    computed: {
        tabIndex() {
            return this.bootstrapTabs ? this.bootstrapTabs.tabs.indexOf(this) : 0;
        },
        isActive() {
            return this.bootstrapTabs ? this.bootstrapTabs.activeIndex === this.tabIndex : this.active;
        },
    },
    created() {
        this.bootstrapTabs?.register(this);
    },
    beforeUnmount() {
        this.bootstrapTabs?.unregister(this);
    },
};
</script>
