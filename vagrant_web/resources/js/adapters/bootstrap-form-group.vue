<template>
  <component
      v-bind="$attrs"
      :is="groupTag"
      class="materialpool-form-group mb-3"
      :class="[stateClass, {'materialpool-form-row': isHorizontal && groupTag !== 'fieldset', 'was-validated': validated}]"
      :disabled="groupTag === 'fieldset' ? disabled : null"
      :role="groupTag === 'fieldset' ? null : 'group'"
      :aria-invalid="state === false ? 'true' : null"
      :aria-labelledby="groupTag === 'fieldset' && isHorizontal ? labelId : null"
  >
    <div v-if="isHorizontal && groupTag === 'fieldset'" class="materialpool-form-row">
      <component :is="labelTag" v-if="hasLabel || isHorizontal" v-bind="labelAttributes" :class="labelClasses">
        <slot name="label">{{ label }}</slot>
      </component>
      <div ref="content" :class="contentClasses">
        <slot v-bind="slotScope"/>
        <feedback-content v-bind="feedbackProps"/>
      </div>
    </div>
    <template v-else>
      <component :is="labelTag" v-if="hasLabel || isHorizontal" v-bind="labelAttributes" :class="labelClasses">
        <slot name="label">{{ label }}</slot>
      </component>
      <div ref="content" :class="contentClasses">
        <slot v-bind="slotScope"/>
        <feedback-content v-bind="feedbackProps"/>
      </div>
    </template>
  </component>
</template>

<script>
import {formGroupColumnClasses} from './bootstrap-form-group';
import FeedbackContent from './bootstrap-form-feedback.vue';

let formGroupId = 0;

export default {
    name: 'BFormGroup',
    components: {FeedbackContent},
    inheritAttrs: false,

    props: {
        contentCols: {type: [Boolean, Number, String], default: null},
        contentColsSm: {type: [Boolean, Number, String], default: null},
        contentColsMd: {type: [Boolean, Number, String], default: null},
        contentColsLg: {type: [Boolean, Number, String], default: null},
        contentColsXl: {type: [Boolean, Number, String], default: null},
        breakpoint: {type: String, default: null},
        description: {type: String, default: null},
        disabled: {type: Boolean, default: false},
        horizontal: {type: Boolean, default: false},
        invalidFeedback: {type: String, default: null},
        label: {type: String, default: null},
        labelClass: {type: [String, Array, Object], default: null},
        labelCols: {type: [Boolean, Number, String], default: null},
        labelColsSm: {type: [Boolean, Number, String], default: null},
        labelColsMd: {type: [Boolean, Number, String], default: null},
        labelColsLg: {type: [Boolean, Number, String], default: null},
        labelColsXl: {type: [Boolean, Number, String], default: null},
        labelFor: {type: String, default: null},
        labelSize: {type: String, default: null},
        labelSrOnly: {type: Boolean, default: false},
        state: {type: Boolean, default: null},
        validated: {type: Boolean, default: false},
        validFeedback: {type: String, default: null},
    },

    data() {
        formGroupId += 1;
        const baseId = `materialpool-form-group-${formGroupId}`;

        return {
            baseId,
            descriptionId: `${baseId}-description`,
            invalidFeedbackId: `${baseId}-invalid-feedback`,
            labelId: `${baseId}-label`,
            validFeedbackId: `${baseId}-valid-feedback`,
        };
    },

    computed: {
        groupTag() {
            if (!this.labelFor) {
                return 'fieldset';
            }

            return this.isHorizontal ? 'div' : 'div';
        },
        labelTag() {
            return this.labelFor ? 'label' : 'legend';
        },
        hasLabel() {
            return Boolean(this.label || this.$slots.label);
        },
        labelColumnClasses() {
            return formGroupColumnClasses(this, 'label');
        },
        contentColumnClasses() {
            return formGroupColumnClasses(this, 'content');
        },
        isHorizontal() {
            return this.labelColumnClasses.length > 0 || this.contentColumnClasses.length > 0;
        },
        labelAttributes() {
            return {
                for: this.labelFor,
                id: this.hasLabel ? this.labelId : null,
                tabindex: this.labelFor ? null : '-1',
            };
        },
        labelClasses() {
            return [
                this.labelClass,
                ...this.labelColumnClasses,
                {
                    'visually-hidden': this.labelSrOnly,
                    'col-form-label': this.isHorizontal || !this.labelFor,
                    'pt-0': !this.isHorizontal && !this.labelFor,
                    'd-block': !this.isHorizontal && Boolean(this.labelFor),
                    [`col-form-label-${this.labelSize}`]: this.labelSize,
                },
            ];
        },
        contentClasses() {
            return this.isHorizontal
                ? (this.contentColumnClasses.length > 0 ? this.contentColumnClasses : ['col'])
                : [];
        },
        stateClass() {
            return {'is-valid': this.state === true, 'is-invalid': this.state === false};
        },
        describedBy() {
            return [
                this.description ? this.descriptionId : null,
                this.state === false && this.invalidFeedback ? this.invalidFeedbackId : null,
                this.state === true && this.validFeedback ? this.validFeedbackId : null,
            ].filter(Boolean).join(' ') || null;
        },
        slotScope() {
            return {
                ariaDescribedby: this.describedBy,
                descriptionId: this.descriptionId,
                id: this.baseId,
                labelId: this.hasLabel ? this.labelId : null,
            };
        },
        feedbackProps() {
            return {
                description: this.description,
                descriptionId: this.descriptionId,
                invalidFeedback: this.invalidFeedback,
                invalidFeedbackId: this.invalidFeedbackId,
                state: this.state,
                validFeedback: this.validFeedback,
                validFeedbackId: this.validFeedbackId,
            };
        },
    },

    mounted() {
        this.updateAriaDescribedBy();
    },

    updated() {
        this.updateAriaDescribedBy();
    },

    methods: {
        updateAriaDescribedBy() {
            if (!this.labelFor || !this.$refs.content) {
                return;
            }

            const input = document.getElementById(this.labelFor);
            if (input && !this.$refs.content.contains(input)) {
                return;
            }
            if (input && this.describedBy) {
                input.setAttribute('aria-describedby', this.describedBy);
            } else if (input) {
                input.removeAttribute('aria-describedby');
            }
        },
    },
};
</script>
