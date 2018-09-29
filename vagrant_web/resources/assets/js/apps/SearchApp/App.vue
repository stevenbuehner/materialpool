<template>
    <div>
        <main-navbar></main-navbar>
        <router-view class="main-area"></router-view>
    </div>
</template>

<script>
    import mainNavbar from './../../components/navbar/mainNavbar.vue';

    export default {
        name: 'AppWrapper',

        components: {
            mainNavbar
        }
    }
</script>

<style scoped>
    .main-area {
        padding-top: 4.5rem;
    }
</style>