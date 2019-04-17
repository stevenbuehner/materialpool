<template>
    <div class="container">
        <form-input
                v-model="searchInput"
                :placeholder="$t('pool.Insert-bibleverse-here')"
                @keydown.enter="analyseSearchInput"
        />

        <div class="range p-3">
            <bible-content-chapter
                    v-for="(verses, rangeIndex) in ranges"
                    :verses="verses"
                    :caption="toCaption(bibleVerses[rangeIndex], 'long')"
                    :key="'r' + rangeIndex + 'bc' "/>
        </div>


    </div>
</template>

<script>
    import BibleContentVerse from "../../../components/biblecontents/bibleContentVerse";
    import BibleContentChapter from "../../../components/biblecontents/bibleContentChapter";
    import {searchArrayObjectsToSearchArrayItems} from "../../../components/search/searchHelper";
    import formInput from "bootstrap-vue/src/components/form-input/form-input"
    import {BibleVerseService} from '../../../../../vendor/stevenbuehner/bible-verse-bundle/js/out/BibleVerseService_de.js';
    import BibleVerse from '../../../../../vendor/stevenbuehner/bible-verse-bundle/js/in/BibleVerse';

    export default {
        name: "ReadBible",

        data() {
            return {
                bibleUuid: null,

                bibleVerses: [],
                searchInput: '',
                bibleVerseContent: [],

            };
        },

        asyncComputed: {
            ranges: {
                get() {
                    return this.$store.dispatch('biblecontents/getMultiple', this.bibleVerses.map(bv => {
                            return {
                                from: bv.getFrom(),
                                to: bv.getTo(),
                                bibleUuid: this.bibleUuid
                            };
                        })
                    );
                },
                default() {
                    return [];
                },
                deep: true,
                watch() {
                    this.bibleVerses
                }
            },

            materials: {
                get() {
                    const searchData = searchArrayObjectsToSearchArrayItems([this.verses]);

                    return this.$store.dispatch('search/materials', {query: searchData});
                },
                default: null,
                watch() {
                    this.from;
                    this.to;
                }
            }
        },

        methods: {
            initVerses(verses) {

                this.bibleVerses = verses.map((bv) => {
                    return new BibleVerse(bv.from, bv.to);
                });

                this.searchInput = this.bibleVerses.map(bv => BibleVerseService.bibleVerseToString(bv)).join(', ')

            },

            analyseSearchInput() {

                this.$store.dispatch('bibleverses/search', this.searchInput)
                    .then(bibleverses => {
                        this.updateRoute(bibleverses)
                    });

            },

            updateRoute(verses) {

                this.$router.push({
                    name: 'readbible',
                    params: {
                        searchquery: this.fromRangeArrayToString(verses)
                    }
                });

            },

            fromStringToRangeArray(text) {

                const query       = text || '';
                const verseranges = query.split(',');

                return verseranges
                    .filter(text => text !== '')
                    .map((range) => {
                        const split = range.split('-');

                        return {
                            from: parseInt(split[0]),
                            to: parseInt(split[1]),
                            bibleId: split.length > 2 ? parseInt(split[2]) : null
                        };
                    });
            },

            fromRangeArrayToString(verseranges) {
                return verseranges.map(bv => {
                    return bv.from + '-' + bv.to + (bv.bibleId ? '-' + bv.bibleId : '');
                }).join(',');
            },

            toCaption(bibleverse, displayLength) {
                return BibleVerseService.bibleVerseToString(bibleverse, displayLength);
            }
        },

        beforeRouteEnter(to, from, next) {
            next(vm => {
                vm.initVerses(vm.fromStringToRangeArray(to.params.searchquery || ''));
            });
        },

        beforeRouteUpdate(to, from, next) {
            this.initVerses(this.fromStringToRangeArray(to.params.searchquery || ''));
            next();
        },

        components: {
            BibleContentChapter,
            BibleContentVerse,
            formInput
        },

    }
</script>

<style scoped>

</style>