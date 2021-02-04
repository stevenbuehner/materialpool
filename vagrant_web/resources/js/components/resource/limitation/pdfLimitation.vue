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

      if (this.pages.length === 1) {
        return this.$tc('pool.Page', this.pages.length) + ' ' + this.pageRange
      } else {
        return this.pageRange + ' ' + this.$tc('pool.Page', this.pages.length);
      }

    }
  }
}
</script>

<style scoped>

</style>