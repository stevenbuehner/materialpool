<template>
  <div>
    <main-navbar/>

    <flash-message class="flashMessageHolder"/>

    <router-view class="main-area"/>
  </div>
</template>

<script>
import mainNavbar                   from '../../components/navbar/mainNavbar.vue';

export default {
  name: 'AppWrapper',

  components: {
    mainNavbar
  }
}
</script>

<style lang="scss">
@import "../../../../node_modules/vue-flash-message/dist/vue-flash-message.min";

.main-area {
  padding-top: 4.5rem;
}

.flashMessageHolder {
  position: fixed;
  top: 1em;
  right: 1em;
  z-index: 99999;
}
</style>
