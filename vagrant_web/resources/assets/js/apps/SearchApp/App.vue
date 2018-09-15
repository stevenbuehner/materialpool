<template>
    <div>
        <main-navbar class="mb-2"></main-navbar>
        <router-view class="container"></router-view>
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
    .container {
        padding-top: 4.5rem;
    }
</style>