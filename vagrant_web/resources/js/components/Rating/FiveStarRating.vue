<template>
    <RatingControl
        v-bind="{...$props, ...$attrs}"
        @rating-selected="$emit('rating-selected', $event)"
        @current-rating="$emit('current-rating', $event)"
        @update:rating="$emit('update:rating', $event)"
        @hover:rating="$emit('hover:rating', $event)"
    />
</template>

<script>
import RatingControl from './RatingControl.vue';

export default {
    name: 'five-star-rating',
    inheritAttrs: false,
    extends: RatingControl,
    components: {
        RatingControl,
    },
    props: {
        maxRating: {
            type: Number,
            default: 20,
        },
        starCount: {
            type: Number,
            default: 5,
        },
        starSize: {
            type: Number,
            default: 18,
        },
        activeColor: {
            type: [String, Array],
            default: 'black',
        },
        inactiveColor: {
            type: String,
            default: 'lightgray',
        },
        inline: {
            type: Boolean,
            default: true,
        },
    },
};
</script>
