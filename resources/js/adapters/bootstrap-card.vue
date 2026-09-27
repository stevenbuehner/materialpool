<template>
  <component v-bind="$attrs" :is="tag" class="card" :class="cardClasses">
    <div v-if="$slots.header || header" class="card-header" :class="headerClass">
      <slot name="header">{{ header }}</slot>
    </div>
    <slot v-if="noBody"/>
    <div v-else class="card-body" :class="bodyClass"><slot/></div>
    <div v-if="$slots.footer || footer" class="card-footer" :class="footerClass">
      <slot name="footer">{{ footer }}</slot>
    </div>
  </component>
</template>

<script>
export default {
    name: 'BCard',
    inheritAttrs: false,
    props: {
        bgVariant: {type: String, default: null},
        bodyClass: {type: [String, Array, Object], default: null},
        borderVariant: {type: String, default: null},
        footer: {type: String, default: null},
        footerClass: {type: [String, Array, Object], default: null},
        header: {type: String, default: null},
        headerClass: {type: [String, Array, Object], default: null},
        noBody: {type: Boolean, default: false},
        tag: {type: String, default: 'div'},
        textVariant: {type: String, default: null},
    },
    computed: {
        cardClasses() {
            return {
                [`bg-${this.bgVariant}`]: this.bgVariant,
                [`border-${this.borderVariant}`]: this.borderVariant,
                [`text-${this.textVariant}`]: this.textVariant,
            };
        },
    },
};
</script>
