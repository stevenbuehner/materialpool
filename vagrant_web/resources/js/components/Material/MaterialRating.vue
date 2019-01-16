<script>

    import starRating from 'vue-star-rating';

    export default {
        name: "MaterialRating",
        extends: starRating,

        computed: {
            formattedRating() {
                const rating = (this.fixedPoints === null) ? this.currentRating : this.currentRating.toFixed(this.fixedPoints)
                return this.$t('pool.rating-' + rating);
            },
        }
    }
</script>

<style scoped>

</style>