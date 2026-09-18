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
                    class="btn btn-link btn-sm rating-reset"
                    type="button"
                    :disabled="disabled || loading"
                    @click="$emit('reset')">
                {{ $t('pool.Reset-own-rating') }}
            </button>
            <span class="visually-hidden">{{ ratingSourceText }}</span>

        </div>
    </div>
</template>

<script>
	import generalMixin   from './generalSidebarFields.mixin';
	import feedbackIcon   from '@icons/vendor/svg-icon/svg/zero/oil-table-chart.svg';
	import FiveStarRating from "../Rating/FiveStarRating";


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
		}
	}
</script>

<style lang="scss" scoped>

    @use "../../../sass/theme" as *;

    .ratingEditSidebarField {
        --material-rating-active-colour: #{$gray-600};
        color: $gray-600;

        &.has-user-ranking {
            --material-rating-active-colour: #{$tag-progressbar-colour};
            color: $tag-progressbar-colour;
        }

        .vue-star-rating {
            line-height: 1;
        }

        .vue-star-rating-rating-text {
            // font-size: 0.9em;
        }

        .rating-reset {
            color: inherit;
            padding-left: 0;
        }
    }

</style>
