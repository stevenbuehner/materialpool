<template>
  <span class="userName">{{ user.name }}
    <template v-if="user.email">
       (<a class="email" :href="'mailto:' + user.email">{{ user.email }}</a>)
    </template>
  </span>
</template>

<script>
export default {
  name: "user-name",

  props: {
    user: {
      type: Object,
      required: true,
    }
  }
}
</script>

<style lang="scss">
.userName {
  .email {
    font-size: inherit;
  }
}
</style>