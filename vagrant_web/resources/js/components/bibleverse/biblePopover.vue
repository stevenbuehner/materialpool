<template>
    <span class="sbTextBibleverse"
          :class="{contentLoadable: loadContents, popoverOpened : showPopover}"
          :id="uid"
          @mouseover="showHovered = true"
          @mouseout="showHovered = false"
          @click="showClicked = !showClicked; showHovered = false"
    >{{text}}<b-popover
            v-if="loadContents"
            placement="auto"
            :target="uid"
            :show="showPopover && !forceClose"
            @show="onShow"
            boundary=".main-area"
            triggers="manual">
        <template slot="title">
            <div class="d-flex justify-content-between align-items-end">
                <span v-if="!contentDoneLoading">
                    {{text}}
                </span>
                <span v-else>
                    {{text}}
                    <span v-if="contentDoneLoading && bibleTranslation">({{bibleTranslation}})</span>
                </span>
            <button class="btn btn-sm btn-light pt-0 pr-2 pb-0 pl-2" v-show="showClicked"
                    @click="showClicked=false"
                    :title="$t('pool.close')">x</button>
            </div>
        </template>

        <span v-if="contentDoneLoading">
            <span v-for="b in bibleContent" class="verseWrapper">
                <span class="number">{{getVerse(b.verse)}}</span>
                <span class="text" :data-verse-no="b.verse">{{b.text}}</span>
            </span>
        </span>
        <span v-else>
            {{$t('pool.Loading-from-bible')}}
        </span>

    </b-popover>


    </span>
</template>

<script>
    import bPopover from 'bootstrap-vue/src/components/popover/popover'
    import BibleVerse from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/in/BibleVerse';
    import {BibleVerseService} from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/out/BibleVerseService_de';

    export default {
        name: "biblePopover",
        props: {
            text: {
                required: true,
                default: 'missing'
            },

            loadContents: {
                type: Boolean,
                required: false,
                default: false
            },
        },


        data() {
            return {
                showHovered: false,
                showClicked: false,

                contentDoneLoading: false,
                forceClose: false,
            };
        },

        computed: {
            uid() {
                return 'id_' + Math.random();
            },

            bibleTranslation() {
                if (this.bibleContent.length > 0 && this.bibleContent[0].bible) {
                    return this.bibleContent[0].bible.title;
                } else {
                    return '';
                }
            },

            showPopover() {
                return this.showHovered || this.showClicked;
            },

        },

        methods: {
            getVerse(verse) {
                return (new BibleVerse(verse, verse)).getFromVerse();
            },

            getVerseDisplay(verse) {
                const bv = this.getVerse(verse);
                return BibleVerseService.bibleVerseToString(verse, 'long');
            },

            onShow() {
                // Request bibleContent
                this.bibleContent;
            },

            onContentDoneLoading() {
                this.contentDoneLoading = true;
                this.forceClose         = true;
                this.$nextTick(() => {
                    this.forceClose = false;
                })
            }
        },

        asyncComputed: {
            bibleContent: {
                get() {
                    return this.$store.dispatch('biblecontents/searchAndGet', {search: this.text})
                        .then((bibleverses) => {
                            this.onContentDoneLoading();
                            return bibleverses.sort((a, b) => a.verse - b.verse);
                        });
                },
                default: [],
                lazy: true,
                watch() {
                },
            },

        },

        components: {
            bPopover
        }
    }
</script>

<style type="scss">
    @import "../../../sass/theme";

    .sbTextBibleverse {
        text-decoration: none;
        border-bottom: 1px dotted gray;
        cursor: pointer;

        &.contentLoadable:hover, &.popoverOpened {
            border-bottom-style: solid;
            border-bottom-color: $link-color;
            color: $link-color;
        }
    }

    .verseWrapper {
        .number {
            font-weight: bold;
        }

        &:not(:first-child) {
            padding-left: .75em;
        }
    }

</style>