<template>
    <div
        :class="['vue-star-rating', {'vue-star-rating-rtl': rtl, 'vue-star-rating-inline': inline}]"
        role="slider"
        :aria-valuemin="0"
        :aria-valuemax="maxRating"
        :aria-valuenow="selectedRating"
        :aria-valuetext="ratingText"
        :aria-disabled="readOnly"
        :tabindex="readOnly ? -1 : 0"
        @keydown="onKeydown"
        @mouseleave="resetRating"
    >
        <span
            v-for="starIndex in resolvedStarCount"
            :key="starIndex"
            :class="['vue-star-rating-star', {'vue-star-rating-pointer': !readOnly}]"
            :style="{'margin-right': padding + borderWidth + 'px'}"
            @pointermove="setRatingFromPointer($event, starIndex - 1, false)"
            @click="setRatingFromPointer($event, starIndex - 1, true)"
        >
            <svg :height="starSize" :width="starSize" :viewBox="`0 0 ${viewBoxSize} ${viewBoxSize}`" aria-hidden="true">
                <defs>
                    <linearGradient :id="gradientId(starIndex)" x1="0" x2="100%" y1="0" y2="0">
                        <stop :offset="`${fillForStar(starIndex - 1)}%`" :stop-color="activeColorForStar(starIndex - 1)" />
                        <stop :offset="`${fillForStar(starIndex - 1)}%`" :stop-color="inactiveColor" />
                    </linearGradient>
                    <filter v-if="glow > 0" :id="glowId(starIndex)" height="130%" width="130%" filterUnits="userSpaceOnUse">
                        <feGaussianBlur :stdDeviation="glow" result="coloredBlur" />
                        <feMerge>
                            <feMergeNode in="coloredBlur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                </defs>
                <polygon
                    :points="points"
                    :fill="`url(#${gradientId(starIndex)})`"
                    :stroke="borderColorForStar(starIndex - 1)"
                    :stroke-width="effectiveBorderWidth"
                    :stroke-linejoin="roundedCorners ? 'round' : 'miter'"
                    :filter="glow > 0 ? `url(#${glowId(starIndex)})` : undefined"
                />
            </svg>
        </span>
        <span v-if="showRating" :class="['vue-star-rating-rating-text', textClass]">{{ ratingText }}</span>
    </div>
</template>

<script>
import {fillPercentage, normalizeRating, ratingFromPointer, roundToIncrement} from './ratingMath';
import {isValidMaxRating} from './ratingValidation';

const defaultStarPoints = [19.8, 2.2, 6.6, 43.56, 39.6, 17.16, 0, 17.16, 33, 43.56];

export default {
    name: 'rating-control',
    emits: ['rating-selected', 'current-rating', 'update:rating', 'hover:rating'],
    props: {
        increment: {type: Number, default: 1},
        rating: {type: Number, default: 0},
        maxRating: {type: Number, validator: isValidMaxRating, default: 5},
        starCount: {type: Number, default: null},
        starPoints: {type: Array, default: () => []},
        starSize: {type: Number, default: 50},
        showRating: {type: Boolean, default: true},
        fixedPoints: {type: Number, default: null},
        ratingDisplayCallback: {type: Function, default: (rating) => rating},
        readOnly: {type: Boolean, default: false},
        textClass: {type: String, default: ''},
        inline: {type: Boolean, default: false},
        activeColor: {type: [String, Array], default: '#ffd055'},
        inactiveColor: {type: String, default: '#d8d8d8'},
        borderColor: {type: String, default: '#999'},
        activeBorderColor: {type: [String, Array], default: null},
        borderWidth: {type: Number, default: 0},
        roundedCorners: {type: Boolean, default: false},
        padding: {type: Number, default: 0},
        rtl: {type: Boolean, default: false},
        glow: {type: Number, default: 0},
    },
    data() {
        return {
            currentRating: normalizeRating(this.rating, this.maxRating),
            selectedRating: normalizeRating(this.rating, this.maxRating),
            componentId: Math.random().toString(36).slice(2),
        };
    },
    computed: {
        resolvedStarCount() {
            return this.starCount ?? this.maxRating;
        },
        displayedPoints() {
            return this.starPoints.length > 0 ? this.starPoints : defaultStarPoints;
        },
        points() {
            return this.displayedPoints.join(',');
        },
        viewBoxSize() {
            return Math.max(...this.displayedPoints);
        },
        effectiveBorderWidth() {
            return this.roundedCorners && this.borderWidth <= 0 ? 6 : this.borderWidth;
        },
        ratingText() {
            const rating = this.fixedPoints === null ? this.currentRating : this.currentRating.toFixed(this.fixedPoints);

            return this.formatRating(rating);
        },
    },
    watch: {
        rating(value) {
            this.currentRating = normalizeRating(value, this.maxRating);
            this.selectedRating = this.currentRating;
        },
    },
    methods: {
        formatRating(rating) {
            return this.ratingDisplayCallback(rating);
        },
        fillForStar(starIndex) {
            return fillPercentage(this.currentRating, starIndex, this.resolvedStarCount, this.maxRating);
        },
        colorForStar(color, starIndex, fallback) {
            if (!Array.isArray(color)) {
                return color ?? fallback;
            }

            return color[starIndex] ?? color[color.length - 1] ?? fallback;
        },
        activeColorForStar(starIndex) {
            return this.colorForStar(this.activeColor, starIndex, '#ffd055');
        },
        borderColorForStar(starIndex) {
            const activeColor = this.colorForStar(this.activeBorderColor, starIndex, this.borderColor);

            return this.fillForStar(starIndex) > 0 ? activeColor : this.borderColor;
        },
        gradientId(starIndex) {
            return `rating-gradient-${this.componentId}-${starIndex}`;
        },
        glowId(starIndex) {
            return `rating-glow-${this.componentId}-${starIndex}`;
        },
        setRatingFromPointer(event, starIndex, persist) {
            if (this.readOnly) {
                return;
            }

            const bounds = event.currentTarget.getBoundingClientRect();
            const position = Math.max(0, Math.min(1, (event.clientX - bounds.left) / bounds.width));
            const rating = ratingFromPointer(position, starIndex, this.resolvedStarCount, this.maxRating, this.increment, this.rtl);

            this.currentRating = rating;

            if (persist) {
                this.selectedRating = rating;
                this.$emit('rating-selected', rating);
                this.$emit('update:rating', rating);
                return;
            }

            this.$emit('current-rating', rating);
            this.$emit('hover:rating', rating);
        },
        resetRating() {
            if (!this.readOnly) {
                this.currentRating = this.selectedRating;
            }
        },
        selectRating(rating) {
            const selectedRating = normalizeRating(rating, this.maxRating);

            this.currentRating = selectedRating;
            this.selectedRating = selectedRating;
            this.$emit('rating-selected', selectedRating);
            this.$emit('update:rating', selectedRating);
        },
        onKeydown(event) {
            if (this.readOnly) {
                return;
            }

            const increase = this.rtl ? -this.increment : this.increment;
            const actions = {
                ArrowUp: () => this.selectedRating + this.increment,
                ArrowRight: () => this.selectedRating + increase,
                ArrowDown: () => this.selectedRating - this.increment,
                ArrowLeft: () => this.selectedRating - increase,
                Home: () => 0,
                End: () => this.maxRating,
            };
            const action = actions[event.key];

            if (!action) {
                return;
            }

            event.preventDefault();
            this.selectRating(roundToIncrement(action(), this.increment, this.maxRating));
        },
    },
};
</script>

<style scoped>
.vue-star-rating { display: flex; align-items: center; outline: none; }
.vue-star-rating:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }
.vue-star-rating-inline { display: inline-flex; }
.vue-star-rating-rtl { direction: rtl; }
.vue-star-rating-star { display: inline-block; line-height: 0; }
.vue-star-rating-pointer { cursor: pointer; }
.vue-star-rating-rating-text { margin-top: 7px; margin-left: 7px; }
.vue-star-rating-rtl .vue-star-rating-rating-text { margin-right: 7px; margin-left: 0; }
</style>
