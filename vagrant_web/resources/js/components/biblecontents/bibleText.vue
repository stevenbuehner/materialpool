<template>
    <div>
        <bible-text-caption :bibleverse="bibleverse" :translation="translation"/>

        <bible-text-portion :verses="verses" :bible="bible" v-if="!versesAreReloading"/>

        <hollow-dots-spinner :dot-size="10"
                             :dots-num="3"
                             :animation-duration="1500"
                             color="grey"
                             v-if="versesAreReloading"/>
    </div>
</template>

<script>
    import BibleTextCaption from "./bibleTextCaption";
    import BibleTextPortion from "./bibleTextPortion";
    import BibleTextVerse from "./bibleTextVerse";
    import {BibleVerseService} from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/out/BibleVerseService_de.js';
    import BibleVerse from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/in/BibleVerse.js';
    import HollowDotsSpinner from "epic-spinners/src/components/lib/HollowDotsSpinner";

    export default {
        name: "bibleText",
        components: {
            HollowDotsSpinner, BibleTextVerse, BibleTextPortion, BibleTextCaption
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