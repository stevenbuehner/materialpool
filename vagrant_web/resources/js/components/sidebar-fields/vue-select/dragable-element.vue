<template>
    <div class="selected-tag draggable-element"
         :class="{draggable : !disableMoveRelevance}"
         v-bind:key="id"
         @mousedown.left="!disableMoveRelevance && keydownStartDrag">

        <div class="selected-relevance"
             :class="{isDragging : dragging.ongoing}"
             :style="{width: displayedRelevance/maxRelevance*100 + '%'}"></div>

        <div class="text">
            <slot name="label">{{label}}</slot>

            <button @click="$emit('deselect')"
                    type="button"
                    v-if="!disableRemoveElement"
                    class="vs__deselect"
                    aria-label="Remove option">

                <span aria-hidden="true"><slot name="label">&times;</slot></span>
            </button>
        </div>

    </div>
</template>

<script>

	import {draggingSupport}    from './../../keyword/dragging.mixin';
	import {RELEVANCE_USER_MAX} from "../../../apps/config";

	export default {
		name: "dragable-element",

		mixins: [draggingSupport],

		props: {
			maxRelevance: {
				type: Number,
				default: RELEVANCE_USER_MAX
			},
			relevance: {
				type: Number,
				default: 0
			},
			label: {
				default: 'no label'
			},
			id: {
				required: true
			},

			disableRemoveElement: {
				type: Boolean,
				default: false
			},
			disableMoveRelevance: {
				type: Boolean,
				default: false
			}
		},

		computed: {
			displayedRelevance() {
				if (this.dragging.ongoing === true) {
					return this.dragPercentage * RELEVANCE_USER_MAX;
				} else {
					return this.relevance;
				}
			}
		},

		created() {
			this.$on('dragging-done', (dragPercentage) => {
				this.requestUpdateRelevance(dragPercentage);
			});
		},

		methods: {
			requestUpdateRelevance(dragPercentage) {
				this.$emit('request-update-relevance', Math.round(dragPercentage * this.maxRelevance));
			},

			keydownStartDrag(event) {
				event.stopPropagation();
				this.startDrag(event);
			},
		}
	}
</script>

<style type="scss">
    @import "../../../../sass/theme";


    .selected-tag {
        display: inline-block;
        position: relative;
        background-color: $sidebar-tag-background-color-active;
        border: $vs-selected-border-width $vs-selected-border-style $vs-selected-border-color;
        border-radius: $vs-border-radius;
        color: $sidebar-input-font-color-active;
        line-height: $vs-component-line-height;
        margin: .25em .25em 0 0;
        padding: 0 0.25em;

        &.draggable {
            cursor: pointer;
        }

        &:hover {
            background-color: $sidebar-tag-background-color-active-hover;
            color: $sidebar-input-font-color-active-hover;

            .selected-relevance {
                background-color: $sidebar-tag-relevance-colour-active-hover;
            }
        }

        .selected-relevance {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            background-color: $sidebar-tag-relevance-colour-active;

            &.isDragging {
                background-color: $sidebar-tag-relevance-colour-active-dragging;
            }
        }

        .text {
            display: inline;
            position: relative;
            user-select: none;
        }

    }
</style>