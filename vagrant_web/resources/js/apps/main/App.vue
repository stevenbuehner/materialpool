<template>
    <div>
        <main-navbar></main-navbar>

        <flash-message class="flashMessageHolder"/>

        <router-view class="main-area"></router-view>
    </div>
</template>

<script>
	import mainNavbar   from '../../components/navbar/mainNavbar.vue';
	import flashMessage from 'vue-flash-message';
	import Vue          from 'vue';

	Vue.use(flashMessage, {
		messageOptions: {
			timeout: 3000,
			important: false,
			pauseOnInteract: true
		}
	});

	export default {
		name: 'AppWrapper',

		components: {
			mainNavbar
		}
	}
</script>

<style type="scss">
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
