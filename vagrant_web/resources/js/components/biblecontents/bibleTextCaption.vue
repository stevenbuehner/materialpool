<template>
    <h1 class="bibleTextCaption d-flex justify-content-between align-items-baseline">
        <slot>
            {{bibleverseCaption}}
            <span v-if="translation" class="small">{{translation}}</span>
        </slot>
    </h1>
</template>

<script>
	import {BibleVerseService} from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/out/BibleVerseService_de.js';
	import BibleVerse          from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/in/BibleVerse.js';

	export default {
		name: "bibleTextCaption",

		props: {
			bibleverse: {
				validator: function (value) {
					return value instanceof BibleVerse;
				},
				required: true
			},

			translation: {
				type: String,
				required: false,
				default: ''
			}


		},

		computed: {
			bibleverseCaption() {
				return BibleVerseService.bibleVerseToString(this.bibleverse, 'long');
			}
		}
	}
</script>

<style lang="scss">
    @import "../../../sass/theme";

    .bibleTextCaption {
        color: $cyan;
        border-bottom: $cyan 0.05em solid;
    }

</style>