<template>
  <div class="from-bot-wrapper" @click="toggleRequest">
    <div class="btn btn-sm from-bot" :class="{'isBot' : fromBot, 'notBot' : !fromBot}">
            <span class="icon"
                  :style="{backgroundImage : fromBot ? 'url(/img/icons/entypo-plus/laptop.svg)' : 'url(/img/icons/entypo-plus/fingerprint.svg)', }"></span>
      <span class="text">{{ label }}</span>
    </div>
  </div>
</template>

<script>

export default {
  name: "from-bot",
  props: {
    fromBot: {
      type: Boolean,
      required: true
    }
  },

  computed: {

    label() {
      return this.fromBot ? 'from bot' : 'by user';
    }
  },

  methods: {
    toggleRequest() {
      this.$emit('toggleRequest');
    }
  }
}
</script>

<style scoped lang="scss">

@import "../../sass/theme";

.from-bot-wrapper {
  float: left;
  position: relative;
  margin-bottom: 0.5rem;
  margin-right: 0.25rem;
}

.from-bot {
  color: $tag-font-colour;
  background-color: $red;
  border: 1px solid $red;
}

.from-bot:hover {
  background-color: $red-hover;
  border-color: $red-hover;
}

.notBot {
  background-color: $tag-progressbar-colour;
  border-color: $tag-progressbar-colour;
  transition: background-color .5s, border-color .5s;
}

.notBot:hover {
  background-color: $tag-progressbar-colour-hover;
  border-color: $tag-progressbar-colour-hover;
}

.from-bot .text {
  text-shadow: .05em .05em .2em $tag-background-colour-hover;
}

.icon {
  position: relative;
  display: inline-block;
  background-size: contain;
  background-position: 0 0;
  height: 0.9rem;
  background-repeat: no-repeat;
  top: 0.1rem;
  width: 1rem;
  margin-right: 0.1rem;
  margin-left: 0;
}
</style>