<template>
  <span>{{ formatedLimitation }}</span>
</template>

<script>

import {getLimitationRangeFromPages} from "./limitationHelper";

export default {


  props: {
    pivot: {
      required: true
    }
  },

  computed: {

    pages() {
      return this.pivot && this.pivot.limitation && this.pivot.limitation.pages ? this.pivot.limitation.pages : [];
    },

    pageRange() {
      return getLimitationRangeFromPages(this.pages).map((r) => {
        return r.from === r.to ? r.from : r.from + '-' + r.to
      }).join(', ')
    },

    formatedLimitation() {

      if (this.pivot === null) {
        return '';
      }

      const limitCount = this.pages.length === 0 ? -1: this.pages.length;

      return this.$tc('pool.Page-Range', limitCount, {COUNT: this.pageRange});

    }
  }
}
</script>

<style scoped>

</style>