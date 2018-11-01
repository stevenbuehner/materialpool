<template>
    <div>
        <main-navbar></main-navbar>

        <flash-message class="flashMessageHolder">
        </flash-message>

        <router-view class="main-area"></router-view>
    </div>
</template>

<script>
    import mainNavbar from '../../components/navbar/mainNavbar.vue';
    import flashMessage from 'vue-flash-message';
    import Vue from 'vue';

    Vue.use(flashMessage);

    export default {
        name: 'AppWrapper',

        components: {
            mainNavbar
        }
    }
</script>

<style>
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
