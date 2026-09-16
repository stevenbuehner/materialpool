<script>

import starRating from '@/adapters/star-rating';

export default {
  name: "MaterialRating",
  extends: starRating,

  methods: {
    formatRating(rating) {
      return this.$t('pool.rating-' + rating);
    },
  },
}
</script>

<style scoped>

</style>
