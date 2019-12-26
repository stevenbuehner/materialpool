<template>
    <span>{{user.name}} (<a class="email" :href="'mailto:' + user.email">{{user.email}}</a>)</span>
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

<style scoped>

</style>