<template>
    <div>
        <bible-text-caption :bibleverse="bibleverse" :translation="translation"/>

        <bible-text-portion :verses="verses" :bible="bible" v-if="!versesAreReloading"/>

        <materialpool-spinner v-if="versesAreReloading"/>
    </div>
</template>

<script>
	import BibleTextCaption    from "./bibleTextCaption";
	import BibleTextPortion    from "./bibleTextPortion";
	import BibleTextVerse      from "./bibleTextVerse";
	import BibleVerse          from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/in/BibleVerse.js';
	import MaterialpoolSpinner from "../spinner/materialpool-spinner";

	export default {
		name: "bibleText",
		components: {
			MaterialpoolSpinner,
			BibleTextVerse, BibleTextPortion, BibleTextCaption
		},

		props: {
			bibleverse: {
				validator: function (value) {
					return value instanceof BibleVerse;
				},
				required: true
			},

			bibleUuid: {
				type: String,
				required: false,
				default: null
			}
		},

		data() {
			return {
				versesAreReloading: false,
			};
		},

		asyncComputed: {
			verses: {
				get() {
					this.versesAreReloading = true;

					return this.$store.dispatch('biblecontents/get', {
						from: this.bibleverse.getFrom(),
						to: this.bibleverse.getTo(),
						bibleUuid: this.bibleUuid
					}).then(data => {
						this.versesAreReloading = false;
						return data;
					});
				},
				default: [],
				watch() {
					this.bibleverse;
					this.bibleUuid;
				}
			},

			bible: {
				get() {
					if (this.bibleUuid) {
						return this.$store.dispatch('bibles/get', this.bibleUuid);
					} else {
						return {};
					}
				},
				default: {},
				watch() {
					this.bibleUuid;
				}
			}
		},

		computed: {
			translation() {
				return this.bibleUuid ? this.bible.title : '';
			},
		}

	}
</script>

<style scoped>

</style>