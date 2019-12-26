<template>
    <div class="container-fluid">

        <h1>Keyword Detail</h1>

        <keyword-edit
                :id="id"
        ></keyword-edit>

        {{relationsCount}}

    </div>
</template>

<script>
	import KeywordEdit from "../../../components/keyword/keywordEdit";


	export default {
		name: "KeywordDetail",
		components: {KeywordEdit},

		props: {
			id: {
				type: Number,
				required: true
			}
		},

		data() {
			return {
				forceReload: 0,
			}
		},

		asyncComputed: {
			keyword: {
				get() {
					return this.$store.dispatch('keywords/get', this.id);
				},
				default: null,
				/* watch() {
                    this.forceReload
                }*/
			},

			relationsCount: {
				get() {
					return this.$store.dispatch('keywords/relationsCount', this.id);
				},
				default: null,
			}
		},


	}
</script>

<style scoped>

</style>