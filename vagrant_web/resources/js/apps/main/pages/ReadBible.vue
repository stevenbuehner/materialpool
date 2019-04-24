<template>
    <div class="container">

        <div id="ReadBibleInputGroup" class="pb-3">
            <b-input-group>
                <b-form-input
                        v-model="searchInput"
                        :placeholder="$t('pool.Insert-bibleverse-here')"
                        @keydown.enter="analyseSearchInput"/>

                <b-dropdown :text="dropDownLabel" variant="secondary" slot="append"
                            :title="$t('pool.Select-Translation')">
                    <b-dropdown-item v-for="bible in allBibles" :key="bible.uuid" @click="bibleUuid=bible.uuid">
                        {{bible.title}}
                    </b-dropdown-item>
                </b-dropdown>
            </b-input-group>
        </div>

        <div class="range pt-3 jumbotron">
            <bible-text
                    v-for="(bv, rangeIndex) in bibleVerses"
                    :key="'bvr' + rangeIndex"
                    :bible-uuid="bibleUuid"
                    :bibleverse="bv"
            ></bible-text>
        </div>


    </div>
</template>

<script>
    import {searchArrayObjectsToSearchArrayItems} from "../../../components/search/searchHelper";
    import formInput from "bootstrap-vue/src/components/form-input/form-input"
    import bFormInput from "bootstrap-vue/src/components/form-input/form-input"
    import bInputGroup from "bootstrap-vue/src/components/input-group/input-group"
    import bInputGroupText from "bootstrap-vue/src/components/input-group/input-group-text"
    import bDropdown from "bootstrap-vue/src/components/dropdown/dropdown"
    import bDropdownItem from "bootstrap-vue/src/components/dropdown/dropdown-item"
    import {BibleVerseService} from '../../../../../vendor/stevenbuehner/bible-verse-bundle/js/out/BibleVerseService_de.js';
    import BibleVerse from '../../../../../vendor/stevenbuehner/bible-verse-bundle/js/in/BibleVerse.js';
    import BibleText from "../../../components/biblecontents/bibleText";

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
            allBibles: {
                get() {
                    return this.$store.dispatch('bibles/getAll').then((bibles) => {
                        return bibles;
                    });
                },
                default: []
            },

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

        computed: {
            selectableBibleOptions() {
                return this.allBibles.map((b) => {
                    return {
                        value: b.uuid,
                        text: b.title
                    };
                })
            },

            dropDownLabel() {
                if (this.bibleUuid) {
                    return this.allBibles.find(b => {
                        return b.uuid === this.bibleUuid;
                    }).title;
                } else {
                    return this.$t('pool.Translation');
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
            BibleText,
            formInput,
            bInputGroup,
            bFormInput,
            bInputGroupText,
            bDropdown,
            bDropdownItem,
        },

    }
</script>

<style scoped>

</style>