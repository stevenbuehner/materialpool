<template>

    <div class="bibleTextPortion pb-3">
        <bible-text-verse v-for="v in verses" :verse="v" :key="'v' + v.verse"/>
    </div>

</template>

<script>
	import BibleTextVerse from "./bibleTextVerse";

	export default {
		name: "bibleTextPortion",

		props: {
			verses: {
				type: Array,
				required: true
			},

			bible: {
				type: Object,
				required: false,
				default: null
			}
		},

		computed: {},

		components: {BibleTextVerse}
	}
</script>


<style lang="scss">
    @use "../../../sass/theme" as *;

    .bibleTextPortion {
        text-align: justify;
    }

</style>
