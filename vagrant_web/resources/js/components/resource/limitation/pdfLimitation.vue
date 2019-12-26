<template>
    <div>{{formatedLimitation}}</div>
</template>

<script>
	export default {

		props: {
			limitation: {
				required: true
			}
		},

		computed: {
			formatedLimitation() {

				if (this.limitation === null) {
					return '';
				}

				if (this.limitation && this.limitation.pages && this.limitation.pages.length > 0) {
					return 'Limitiert auf die Seiten: ' + this.limitation.pages.join(', ');
				}

				return '';

			}
		}
	}
</script>

<style scoped>

</style>