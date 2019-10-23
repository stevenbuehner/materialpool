<template>
    <div class="sideBarField ratingEditSidebarField">

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
                    :rating="value"
                    :rating-display-callback="formatRating"
                    :star-count="5"
                    @rating-selected="onRatingSelected"
            />

        </div>
    </div>
</template>

<script>
    import generalMixin from './generalSidebarFields.mixin';
    import feedbackIcon from 'svg-icon/dist/svg/material/feedback.svg';
    import FiveStarRating from "../Rating/FiveStarRating";


    export default {
        name: "ratingEdit",

        mixins: [generalMixin],

        props: {},

        watch: {},

        data() {
            return {};
        },

        computed: {},

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

<style type="scss">
    //    @import "generalCss";
    //    @import "resources/sass/theme";

    .ratingEditSidebarField {
        .vue-star-rating {
            line-height: 1;
        }

        .vue-star-rating-rating-text {
            // font-size: 0.9em;
        }
    }

</style>