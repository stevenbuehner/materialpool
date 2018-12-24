<template>
    <div class="container">
        <bible-content-chapter v-for="verses in chapter" :verses="verses" :key="'c' + verses[0].verse"/>
    </div>
</template>

<script>

    import Vue from 'vue';
    import AsyncComputed from 'vue-async-computed';
    import BibleContentVerse from "../../../components/biblecontents/bibleContentVerse";
    import BibleContentChapter from "../../../components/biblecontents/bibleContentChapter";
    import {searchArrayObjectsToSearchArrayItems} from "../../../components/search/searchHelper";

    Vue.use(AsyncComputed);

    export default {
        name: "ReadBible",
        props: {
            from: {
                type: Number,
                required: true
            },
            to: {
                type: Number,
                required: true
            },
            bibleId: {
                type: Number,
                required: false,
                default: undefined
            },
        },

        data() {
            return {};
        },

        asyncComputed: {
            chapter: {
                get() {
                    return this.$store.dispatch('biblecontents/get', {
                        from: this.from,
                        to: this.to,
                        bibleId: this.bibleId
                    }).then((verses) => {

                        const chapters = {};

                        // Gruppieren nach Kapitel
                        verses.forEach((el) => {

                            const c = Math.floor(el.verse / 1000) % 1000;

                            if (chapters[c] === undefined) {
                                chapters[c] = [];
                            }

                            chapters[c].push(el);

                        });

                        return chapters;

                    });
                },
                default: null,
                watch() {
                    this.from;
                    this.to;
                    this.bibleId;
                }
            },

            materials: {
                get() {

                    const searchData = searchArrayObjectsToSearchArrayItems([[{from: this.from, to: this.to}]]);
                    console.log(searchData);

                    return this.$store.dispatch('search/materials', {query: searchData});
                },
                default: null,
                watch() {
                    this.from;
                    this.to;
                }
            }
        },

        components: {BibleContentChapter, BibleContentVerse},

    }
</script>

<style scoped>

</style>