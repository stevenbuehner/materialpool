<template>
    <div class="verse" :id="'bv' + bibleId">
        <router-link
                class="verseNumber"
                :to="{name:'readbible', params:{from : verse.verse, to: verse.verse, bibleId:bibleId}}">{{verseNumber}}
        </router-link>
        <div class="verseText">{{text}}</div>
    </div>
</template>

<script>

    export default {
        name: "bibleContentVerse",

        props: {
            verse: {
                type: Object,
                required: true
            }
        },

        computed: {
            text() {
                return this.verse.text;
            },
            verseNumber() {
                return this.verse.verse % 1000;
            },
            bibleId() {
                return this.verse.bible_id;
            },


        }
    }
</script>

<style type="scss">
    @import "../../../sass/theme";

    .verse {
        display: inline;
        margin-right: 0.5em;
        padding-right: 0.25em;
        padding-left: 0.25em;
        cursor: pointer;

        .verseNumber {
            display: inline-block;
            font-size: 1.05em;
            font-weight: bold;
            color: $cyan;

            &:hover {
                text-decoration: none;
            }
        }

        &:hover {
            background-color: $cyan;
            color: white;

            .verseNumber {
                color: white;
            }
        }

        .verseText {
            display: inline;
        }
    }

</style>