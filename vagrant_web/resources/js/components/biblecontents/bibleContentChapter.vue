<template>
    <div class="chapter pb-3 pt-2">
        <h1 class="chapterTitle">{{caption || chapterTitle}}</h1>
        <bible-content-verse v-for="v in verses" :verse="v" :key="'v' + v.verse"/>
    </div>
</template>

<script>

    import BibleContentVerse from "./bibleContentVerse";
    import {BibleVerseService} from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/out/BibleVerseService_de.js';

    export default {
        name: "bibleContentChapter",
        props: {
            verses: {
                type: Array,
                required: true
            },

            caption: {
                type: String,
                required: false,
            }
        },

        computed: {
            chapterTitle() {

                return BibleVerseService._getBookLabel(
                    this.bookId,
                    'long'
                ) + ' ' + this.chapterNumber;
            },

            chapterNumber() {
                return Math.floor(this.verses[0].verse / 1000) % 1000;
            },

            bookId() {
                return Math.floor(this.verses[0].verse / 1000000) % 1000;
            }


        },

        components: {BibleContentVerse},
    }
</script>

<style type="scss">
    @import "../../../sass/theme";

    .chapter {

        .chapterTitle {
            color: $cyan;
            border-bottom: $cyan 0.05em solid;
        }
    }

</style>