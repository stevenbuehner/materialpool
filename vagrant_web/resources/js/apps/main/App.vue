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
// Kompatibilitätsstil nach vue-flash-message 0.7.2 (MIT), lokal gehalten,
// damit der Vue-2-only Plugin-Code nicht mehr Teil der Laufzeit ist.
.flash__message {
  box-sizing: border-box;
  padding: 15px 30px 15px 15px;
  margin-bottom: 10px;
  border: 1px solid transparent;
  border-radius: 4px;
  background-color: #cccccc;
  position: relative;
}

.flash__message.success {
  color: #3c763d;
  background-color: #dff0d8;
  border-color: #d6e9c6;
}

.flash__message.warning {
  color: #8a6d3b;
  background-color: #fcf8e3;
  border-color: #faebcc;
}

.flash__message.error {
  color: #a94442;
  background-color: #f2dede;
  border-color: #ebccd1;
}

.flash__message.info {
  color: #37516a;
  background-color: #f2f5fe;
  border-color: rgb(223, 227, 240);
}

.flash__close-button {
  box-sizing: border-box;
  position: absolute;
  top: 0;
  right: 0;
  color: inherit;
  appearance: none;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 18px;
  line-height: 10px;
  margin: 0;
  padding: 8px;
}

.flash__close-button:active,
.flash__close-button:focus {
  outline: none;
}

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
