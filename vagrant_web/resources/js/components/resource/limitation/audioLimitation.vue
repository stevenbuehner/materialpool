<template>
    <div>{{formatedLimitation}}</div>
</template>

<script>
	export default {

		props: {
			pivot: {
				required: true
			}
		},

		computed: {
			formatedLimitation() {

				if (this.limitation === null) {
					return '';
				}

				if (this.limitation && this.limitation.start && this.limitation.end) {
					return 'Limitiert auf Sekunde ' + this.limitation.start + ' bis ' + this.limitation.end;
				}

				return '';

			}
		}
	}
</script>

<style scoped>

</style>