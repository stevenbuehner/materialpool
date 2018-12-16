<template>
    <div class="chapter">
        <h1 class="chapterTitle">{{chapterTitle}}</h1>
        <bible-content-verse v-for="v in verses" :verse="v"/>
    </div>
</template>

<script>

    import BibleContentVerse from "./bibleContentVerse";
    import {BibleVerseService} from './../../../../../vendor/stevenbuehner/bible-verse-bundle/js/out/BibleVerseService_de.js';

    export default {
        name: "bibleContentChapter",
        props: {
            verses: {
                type: Array,
                required: true
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
    @import "resources/assets/sass/theme.scss";

    .chapter {

        margin-bottom: 2em;

        .chapterTitle {
            color: $cyan;
            border-bottom: $cyan 0.05em solid;
        }

    }

</style>