<template>

    <div class="bibleTextVerse" :id="'bv' + bibleId"
         :class="{selected : isSelected}"
         @click="onClicked">
        <span class="verseNumber">{{verseNumber}}</span>
        <div class="verseText">{{text}}</div>
    </div>

</template>

<script>
    export default {
        name: "bibleTextVerse",

        props: {
            verse: {
                type: Object,
                required: true
            }
        },

        data() {
            return {
                isSelected: false
            };
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
        },

        methods: {
            onClicked() {

                this.$emit('verse-clicked', this.verse);

                this.isSelected = !this.isSelected;

                this.$emit('verse-' + (this.isSelected === true ? 'selected' : 'deselected'), this.verse);

            }
        }
    }
</script>


<style type="scss">
    @import "../../../sass/theme";

    .bibleTextVerse {
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

        &:hover, &.selected {
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