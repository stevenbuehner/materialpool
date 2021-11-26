<template>
  <div>{{ formatedLimitation }}</div>
</template>

<script>
export default {

  name: "videoLimitation",

  props: {
    pivot: {
      required: true
    }
  },

  computed: {

    start() {
      return this.pivot?.limitation?.start;
    },

    end() {
      return this.pivot?.limitation?.end;
    },

    formatedLimitation() {

      if (this.start === null || this.end === null) {
        return '';
      }

      return 'Limitiert auf Sekunde ' + this.start + ' bis ' + this.end;

    }
  }
}
</script>
