<template>
    <div :class="['vue-star-rating', {'vue-star-rating-rtl':rtl}, {'vue-star-rating-inline': inline}]">
        <div @mouseleave="resetRating" class="vue-star-rating">
            <span v-for="(n,i) in fillLevel" :key="i"
                  :class="[{'vue-star-rating-pointer': !readOnly }, 'vue-star-rating-star']"
                  :style="{'margin-right': margin + 'px'}">
              <star :fill="fillLevel[i]" :size="starSize" :points="starPoints" :star-id="i"
                    :active-color="activeColor" :inactive-color="inactiveColor" :border-color="borderColor"
                    :border-width="borderWidth" :rounded-corners="roundedCorners"
                    @star-selected="setRating($event, true)" @star-mouse-move="setRating" :rtl="rtl" :glow="glow"
                    :glow-color="glowColor"></star>
            </span>
            <span v-if="showRating" :class="['vue-star-rating-rating-text', textClass]"> {{ratingDisplayCallback(currentRating)}}</span>
        </div>
    </div>
</template>
<script type="text/javascript">
	import star from 'vue-star-rating/src/star.vue'

	export default {
		name: 'five-star-rating',
		components: {
			star
		},
		model: {
			prop: 'rating',
			event: 'rating-selected'
		},
		props: {
			rating: {
				type: Number,
				default: 0
			},
			roundStartRating: {
				type: Boolean,
				default: true
			},
			activeColor: {
				type: String,
				default: 'black'
			},
			inactiveColor: {
				type: String,
				default: 'lightgray'
			},
			maxRating: {
				type: Number,
				validator(value) {
					if (!typeof value === 'number') {
						console.error('maxRating needs to be of type Number');
						return false;
					}

					if (value <= 0) {
						console.error('maxRating needs to > 0');
						return false;
					}

					return true;
				},
				default: 20
			},
			starCount: {
				type: Number,
				default: 5
			},
			starPoints: {
				type: Array,
				default() {
					return []
				}
			},
			starSize: {
				type: Number,
				default: 18
			},
			showRating: {
				type: Boolean,
				default: true
			},
			ratingDisplayCallback: {
				type: Function,
				default(value) {
					return value;
				}
			},
			readOnly: {
				type: Boolean,
				default: false
			},
			textClass: {
				type: String,
				default: ''
			},
			inline: {
				type: Boolean,
				default: true
			},
			borderColor: {
				type: String,
				default: '#999'
			},
			borderWidth: {
				type: Number,
				default: 0
			},
			roundedCorners: {
				type: Boolean,
				default: false
			},
			padding: {
				type: Number,
				default: 0
			},
			rtl: {
				type: Boolean,
				default: false
			},
			fixedPoints: {
				type: Number,
				default: null
			},
			glow: {
				type: Number,
				default: 0
			},
			glowColor: {
				type: String,
				default: '#fff'
			},

		},
		created() {
			this.currentRating  = this.rating;
			this.selectedRating = this.currentRating
			this.createStars(this.roundStartRating)
		},
		methods: {
			setRating($event, persist) {
				if (!this.readOnly) {
					const position       = (this.rtl) ? (100 - $event.position) / 100 : $event.position / 100;
					const starPercentage = ($event.id + position).toFixed(2) / this.starCount;
					this.currentRating   = Math.round(starPercentage * this.maxRating);

					this.createStars();

					if (persist) {
						this.selectedRating = this.currentRating
						this.$emit('rating-selected', this.selectedRating)
						this.ratingSelected = true
					} else {
						this.$emit('current-rating', this.currentRating)
					}
				}
			},
			resetRating() {
				if (!this.readOnly) {
					this.currentRating = this.selectedRating
					this.createStars(this.shouldRound)
				}
			},
			createStars(round = true) {
				if (round) {
					this.round()
				}

				const absoluteFillPercentage = (this.currentRating / this.maxRating).toFixed(2);
				// const percentagePerStar      = 1 / this.starCount;
				const relativeStarCount      = absoluteFillPercentage * this.starCount;

				for (var i = 0; i < this.starCount; i++) {
					const level = Math.max(0, Math.min(1, relativeStarCount - i)) * 100;
					this.$set(this.fillLevel, i, level);
				}
			},
			round() {
				this.currentRating = Math.round(this.currentRating);
			}
		},
		computed: {
			shouldRound() {
				return this.ratingSelected || this.roundStartRating
			},
			margin() {
				return this.padding + this.borderWidth
			},
		},
		watch: {
			rating(val) {
				this.currentRating  = val;
				this.selectedRating = val;
				this.createStars(this.shouldRound);
			},
			starCount() {
				this.fillLevel = [];
				this.createStars(this.shouldRound);
			}
		},
		data() {
			return {
				fillLevel: [],
				currentRating: 0,
				selectedRating: 0,
				ratingSelected: false
			}
		}
	}
</script>
<style scoped>
    .vue-star-rating-star {
        display: inline-block;
    }

    .vue-star-rating-pointer {
        cursor: pointer;
    }

    .vue-star-rating {
        display: flex;
        align-items: center;
    }

    .vue-star-rating-inline {
        display: inline-flex;
    }

    .vue-star-rating-rating-text {
        margin-top: 7px;
        margin-left: 7px;
    }

    .vue-star-rating-rtl {
        direction: rtl;
    }

    .vue-star-rating-rtl .vue-star-rating-rating-text {
        margin-right: 10px;
        direction: rtl;
    }
</style>