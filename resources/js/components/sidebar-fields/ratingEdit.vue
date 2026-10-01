<template>
    <div :class="['sideBarField', 'ratingEditSidebarField', {'has-user-ranking': hasUserRanking}]">

        <div class="label">
            <slot name="label">
                <slot name="icon">
                    <feedback-icon/>
                </slot>

                <span class="title">
                    <slot name="title">{{name}}</slot>
                </span>
            </slot>
        </div>

        <div class="editField">


            <five-star-rating
                    :rating="effectiveRating"
                    :rating-display-callback="formatRating"
                    :star-count="5"
                    active-color="var(--material-rating-active-colour)"
                    :read-only="disabled || loading"
                    @rating-selected="onRatingSelected"
            />

            <button v-if="hasUserRanking"
                    id="rating-reset"
                    class="btn btn-link btn-sm rating-reset"
                    type="button"
                    :aria-label="$t('pool.Reset-own-rating')"
                    :disabled="disabled || loading"
                    @click="$emit('reset')">
                <undo-icon aria-hidden="true"/>
            </button>
            <b-tooltip v-if="hasUserRanking" target="rating-reset" :title="$t('pool.Reset-own-rating')"/>
            <span class="visually-hidden">{{ ratingSourceText }}</span>

        </div>
    </div>
</template>

<script>
	import generalMixin   from './generalSidebarFields.mixin';
	import feedbackIcon   from '@icons/vendor/svg-icon/svg/zero/oil-table-chart.svg';
	import undoIcon       from '@icons/vendor/svg-icon/svg/material/undo.svg';
	import FiveStarRating from "../Rating/FiveStarRating";
	import {BTooltip} from '@/adapters/bootstrap';


	export default {
		name: "ratingEdit",

		mixins: [generalMixin],

		props: {
			defaultRating: {type: Number, default: null},
			userRating: {type: Number, default: null},
			loading: {type: Boolean, default: false},
		},

		emits: ['input', 'reset'],

		watch: {},

		data() {
			return {};
		},

		computed: {
			hasUserRanking() {
				return Number.isInteger(this.userRating);
			},
			effectiveRating() {
				return this.hasUserRanking ? this.userRating : this.defaultRating;
			},
			ratingSourceText() {
				return this.$t(this.hasUserRanking ? 'pool.Own-rating' : 'pool.Default-rating');
			},
		},

		methods: {

			formatRating(rating) {
				return this.$t('pool.rating-' + rating);
			},

			onRatingSelected(rating) {
				this.$emit('input', rating);
			}
		},

		components: {
			FiveStarRating,
			feedbackIcon,
			undoIcon,
			BTooltip,
		}
	}
</script>

<style lang="scss" scoped>

    @use "../../../sass/theme" as *;

    .ratingEditSidebarField {
        --material-rating-active-colour: #{$gray-600};

        &.has-user-ranking {
            --material-rating-active-colour: #{$tag-progressbar-colour};
        }

        .vue-star-rating {
            line-height: 1;
        }

        .rating-reset {
            color: inherit;
            padding: 0 .25rem;

            svg {
                width: 1em;
                height: 1em;
            }
        }
    }

</style>
