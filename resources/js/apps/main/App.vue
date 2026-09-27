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
  color: var(--bs-body-color);
  background-color: var(--bs-secondary-bg);
  border-color: var(--bs-border-color);
  position: relative;
}

.flash__message.success {
  color: var(--bs-success-text-emphasis);
  background-color: var(--bs-success-bg-subtle);
  border-color: var(--bs-success-border-subtle);
}

.flash__message.warning {
  color: var(--bs-warning-text-emphasis);
  background-color: var(--bs-warning-bg-subtle);
  border-color: var(--bs-warning-border-subtle);
}

.flash__message.error {
  color: var(--bs-danger-text-emphasis);
  background-color: var(--bs-danger-bg-subtle);
  border-color: var(--bs-danger-border-subtle);
}

.flash__message.info {
  color: var(--bs-info-text-emphasis);
  background-color: var(--bs-info-bg-subtle);
  border-color: var(--bs-info-border-subtle);
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
