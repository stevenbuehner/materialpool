<template>
    <div class="bible-popover"
         :style="style">
        <header>
            <h1 @mousedown.prevent.stop="_dragStart($event)"
                @touchstart.prevent.stop="_dragStart($event)">
                <span class="header-left">
                    <span class="action cursorMove" v-if="isPinned">
                        <cursor-move-icon/>
                    </span>
                    <span class="text">{{$t('pool.Bible-reference')}}</span>
                </span>
                <span class="header-actions">
                    <span @click="_togglePin"
                          @mousedown.stop
                          class="action pin enabled" :class="{isPinned}">
                        <pin-icon class="icon" v-if="!isPinned"/>
                        <pin-remove-icon v-if="isPinned"/>
                    </span>

                    <span @click.stop="_doPrevious"
                          @mousedown.stop
                          class="action previous" :class="{enabled: enablePrevious}"
                          v-if="enablePrevious">
                        <double-left-icon/>
                    </span>

                    <span @click="_doNext"
                          @mousedown.stop
                          class="action next" :class="{enabled: enableNext}"
                          v-if="enableNext">
                        <double-right-icon/>
                    </span>
                    
                    <span class="space"></span>

                    <span @click="_doClose"
                          @mousedown.stop
                          class="action close enabled"
                          :title="$t('pool.close')">
                        <close-icon/>
                    </span>

				</span>
            </h1>
        </header>

        <article class="bible-popover-content">
            <div class="article-inner" ref="innercontent">
                <h2>{{bibleverseLabel}}
                    <span class="ref-actions">
                        <router-link class="action-button"
                                     v-if="materialCount"
                                     :to="{name: 'search', params: {search: materialSearchParam}}"
                                     :title="$t('pool.show-materials')">
                            {{$tc('pool.material-count', materialCount, {COUNT: materialCount})}}
                        </router-link>
                        <router-link class="action-button"
                                     target="_blank"
                                     :to="{name: 'readbible', params: {searchquery: readBibleSearchParam}}"
                                     :title="$t('pool.lookup-in-context')">
                            {{$t('pool.context', materialCount, {COUNT: materialCount})}}
                        </router-link>
                    </span>
                </h2>

                <p>
                    <span v-for="vers in verses" class="verse">
                        <span v-if="!isSingleVerse" class="vno">{{vers.vno}}</span>
                        <span class="text">{{vers.text}} </span>
                    </span>
                    <materialpool-spinner v-if="verses.length === 0"/>
                </p>
                <p></p>
                <div class="version" v-if="bible.uuid">
                    <span>{{bible.title}} ({{bible.uuid}})</span><br>
                    <span>{{$t('pool.Source')}}: <a :href="bible.source"
                                                    target="_blank">{{bibleSourceDomain}}</a></span>
                </div>
            </div>
        </article>
    </div>
</template>


/*
Beispiel-Einbettung:
<bible-popover :bibleverse="bibleverse" v-if="showMe" :position="$refs.test"
               @bible-popover-closerequest="showMe = false"/>

Parameter
- bibleverse: vom Typ BibleVerse
- position: vom Typ Element (daran wird die Box ausgerichtet)

Events:
- bible-popover-closerequest: wenn dieses Event geschickt wird, muss die Box wieder ausgeblendet werden

*/

<script>
    import BiblePopoverHeader from "./bible-popover-header";
    import BiblePopoverContent from "./bible-popover-content";
    import eventBus from './bible-popover-eventbus.js';
    import BibleVerse from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/in/BibleVerse.js';
    import {BibleVerseService} from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/out/BibleVerseService_de.js';
    import doubleLeftIcon from './angle-double-left.svg';
    import doubleRightIcon from './angle-double-right.svg';
    import pinIcon from './pin.svg';
    import pinRemoveIcon from './pin-remove.svg';
    import closeIcon from './close.svg';
    import cursorMoveIcon from './cursor-move.svg';
    import {bibleverseToSearchItem, searchArrayObjectsToSearchQuery} from "../search/searchHelper";
    import {fromRangeArrayToString} from "./../../apps/main/pages/ReadBible.vue";
    import MaterialpoolSpinner from "../spinner/materialpool-spinner";


    export default {
        name: "bible-popover",
        provide: {},

        props: {
            bibleverse: {
                required: true,
                validator: function (value) {
                    return value instanceof BibleVerse || (value.from && value.to);
                },
            },
            bibleUuid: {
                type: String,
                required: false,
                default: null
            },

            position: {
                required: false,
                default: 'center',
                validator(value) {
                    return value instanceof Element || value === 'center';
                }
            },

            enablePrevious: {
                type: Boolean,
                default: false,
            },

            enableNext: {
                type: Boolean,
                default: false,
            },

            maxHeight: {
                type: Number,
                default: 300
            }
        },

        data() {
            return {
                isLoading: false, // True, wenn der Bibeltext noch vom Server geladen wird
                isPinned: false, // Pinnnadel gesetzt => Drag and Drop aktiviert und Fenster schließt sich nicht selbst
                originalX: null,
                originalY: null,

                isDragging: false,
                dragOffsetX: 0,
                dragOffsetY: 0,

                width: 400,

                minHeight: 200,

                minMargin: {
                    left: 5,
                    top: 60,
                    right: 5,
                    bottom: 5
                },

                styleData: {
                    left: 0,
                    top: 0,
                    height: 0,
                    width: 0,
                },
                maxRequiredContentHeight: 100,
            };
        },

        computed: {
            normalizedBibleverse() {
                if (this.bibleverse instanceof BibleVerse) {
                    return this.bibleverse;
                } else if (this.bibleverse.from && this.bibleverse.to) {
                    return new BibleVerse(this.bibleverse.from, this.bibleverse.to);
                } else {
                    console.error('Could not normalize bibleverse!');
                }
            },
            from() {
                return this.normalizedBibleverse.getFrom();
            },
            to() {
                return this.normalizedBibleverse.getTo();
            },
            bibleverseLabel() {
                return BibleVerseService.bibleVerseToString(this.normalizedBibleverse, 'long');
            },
            usedBibleTranslationUuid() {
                let bibleUuid = this.bibleUuid;

                if (this.verses.length > 0 && this.verses[0].bibleUuid) {
                    bibleUuid = this.verses[0].bibleUuid;
                }

                return bibleUuid;
            },


            style() {
                return {
                    minHeight: this.boxHeight + 'px',
                    width: this.boxWidth + 'px',
                    left: this.styleData.left + 'px',
                    top: this.styleData.top + 'px'
                };
            },

            isSingleVerse() {
                return this.normalizedBibleverse.isSingleVerse();
            },

            boxHeight() {
                return Math.max(
                    Math.min(
                        window.innerHeight - this.styleData.top - this.minMargin.bottom,
                        this.maxRequiredContentHeight
                    ),
                    Math.min(
                        this.minHeight,
                        this.maxRequiredContentHeight
                    )
                );
            },

            boxWidth() {
                return this.width;
            },

            bibleSourceDomain() {
                if (this.bible && this.bible.source) {
                    return this.bible.source.replace('http://', '').replace('https://', '').split(/[/?#]/)[0];
                } else {
                    return ';'
                }
            },

            materialSearchParam() {
                return searchArrayObjectsToSearchQuery([[this.bibleverse]]);
            },

            readBibleSearchParam() {
                const context = new BibleVerse(this.from, this.to);
                context.setFromVerse(1);
                const book = BibleVerseService._getBibleBook(context.getFromBookId());
                context.setToVerse(book.getVerseCountForChapter(context.getToChapter()));

                return fromRangeArrayToString([context]);
            }
        },


        asyncComputed: {
            verses: {
                get() {
                    this.isLoading = true;

                    return this.$store.dispatch('biblecontents/get', {
                        from: this.from,
                        to: this.to,
                        bibleUuid: this.bibleUuid
                    }).then((data) => {
                        this.isLoading = false;


                        return data.map((v) => {
                            const {bookId, chapter, verse} = BibleVerse.explodeNumber(v.verse);

                            return {
                                bibleUuid: v.bibleUuid,
                                text: v.text,
                                vno: verse,
                                cno: chapter,
                                bno: bookId,
                                no: v.verse
                            };
                        });

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
                    const bibleUuid = this.usedBibleTranslationUuid;

                    if (bibleUuid !== null) {
                        return this.$store.dispatch('bibles/get', bibleUuid)
                    }
                    return {};
                },
                default: {}
            },

            materialCount: {
                get() {

                    return this.$store.dispatch('search/materials', {
                        query: {
                            1:
                                [{
                                    type: 'b',
                                    from: parseInt(this.from).toString(),
                                    to: parseInt(this.to).toString()
                                }]
                        }
                    }).then(({materials, paging}) => {
                        return paging.total;
                    })

                },
                default: null,
                watch() {
                }
            },
        },

        watch: {
            bibleverses() {
                this._initPosition();
            },
            verses() {
                this.$nextTick(() => {
                    this._updateMaxRequiredContentHeight();
                });
            },
            bible() {
                this.$nextTick(() => {
                    this._updateMaxRequiredContentHeight();
                });
            }
        },


        methods: {

            _dragStart(event) {
                this.isDragging = true;
                this.isPinned   = true;
                const rect      = this.$el.getBoundingClientRect();

                if (event.type === "mousedown") {
                    this.dragOffsetX = event.clientX - rect.left;
                    this.dragOffsetY = event.clientY - rect.top;
                    window.addEventListener("mousemove", this._dragMove, true);
                    document.addEventListener("mouseup", this._dragStop, true);
                } else if (event.type === "touchstart") {
                    this.dragOffsetX = event.targetTouches[0].clientX - rect.left;
                    this.dragOffsetY = event.targetTouches[0].clientY - rect.top;
                    window.addEventListener("touchmove", this._dragMove, true);
                    document.addEventListener("touchend", this._dragStop, true);
                }

            },

            _dragMove(event) {
                event.preventDefault();
                event.stopPropagation();

                if (!this.isDragging) {
                    return;
                }

                if (event.type === "mousemove") {
                    this.styleData.left = event.clientX - this.dragOffsetX;
                    this.styleData.top  = event.clientY - this.dragOffsetY;
                } else if (event.type === "touchmove") {
                    this.styleData.left = event.targetTouches[0].clientX - this.dragOffsetX;
                    this.styleData.top  = event.targetTouches[0].clientY - this.dragOffsetY;
                }

                this._checkSizeAndPositionRestrictions();

            },
            _dragStop(event) {
                if (!this.isDragging) {
                    return;
                }

                document.removeEventListener('mouseup', this._dragStop, true);
                window.removeEventListener('mousemove', this._dragMove, true);
                document.removeEventListener('touchend', this._dragStop, true);
                window.removeEventListener('touchmove', this._dragMove, true);

                this.isDragging = false;
            },

            _initPosition() {

                if (this.position instanceof Element) {

                    const rect = this.position.getBoundingClientRect();

                    this.originalX = (rect.left + rect.width / 2) /* center pos of clicked button */;

                    // if possible center the popup underneath the clicked button
                    this.originalX = Math.max(this.originalX - this.boxWidth / 2, this.minMargin.left);

                    this.originalY = rect.top + rect.height /* bottom pos of clicked button */;

                } else if (this.position === 'center') {

                    this.originalX = (window.innerWidth - this.boxWidth) / 2;
                    this.originalY = (window.innerHeight - this.boxHeight) / 2

                }


                this.styleData.left = this.originalX;
                this.styleData.top  = this.originalY;

                this._checkSizeAndPositionRestrictions();

            },

            _checkSizeAndPositionRestrictions() {

                // Check collision with browser-border
                // TOP
                if (this.styleData.top < this.minMargin.top) {
                    this.styleData.top = this.minMargin.top;
                }

                // RIGHT
                if (this.styleData.left + this.boxWidth + this.minMargin.right > window.innerWidth) {
                    this.styleData.left = window.innerWidth - this.boxWidth - this.minMargin.right;
                }

                // BOTTOM
                if (this.styleData.top + this.boxHeight + this.minMargin.bottom > window.innerHeight) {
                    this.styleData.top = window.innerHeight - this.minMargin.bottom - this.boxHeight;
                }

                // LEFT
                if (this.styleData.left < this.minMargin.left) {
                    this.styleData.left = this.minMargin.left;
                }

                // Check height


            },


            _togglePin() {

                if (this.isPinned) {
                    this._initPosition();
                }

                this.isPinned = !this.isPinned;

            },

            _updateMaxRequiredContentHeight() {

                const content = this.$refs.innercontent;

                if (content !== undefined) {
                    this.maxRequiredContentHeight = content.clientHeight /* height + padding */
                        + content.parentElement.offsetTop /* header height */
                        + 2 /* outer border */;
                }

            },


            _doPrevious() {
                eventBus.$emit('bible-popover-previous', this);
            },

            _doNext() {
                eventBus.$emit('bible-popover-next', this);
            },

            _doClose() {
                this.$emit('bible-popover-closerequest');
            },

            _incomingPopoverOpening(instance) {
                if (instance !== this) {
                    this._doClose();
                }
            },

        },

        created() {
            eventBus.$on('bible-popover-opening', this._incomingPopoverOpening);

            this._initPosition();
        },

        mounted() {
            eventBus.$emit('bible-popover-opening', this);
        },

        destroyed() {
            eventBus.$off('bible-popover-opening', this._incomingPopoverOpening);
        },

        components: {
            MaterialpoolSpinner,
            BiblePopoverContent,
            BiblePopoverHeader,
            doubleLeftIcon,
            doubleRightIcon,
            pinIcon, pinRemoveIcon,
            closeIcon,
            cursorMoveIcon
        },

    }
</script>

<style type="scss">

    @import "../../../sass/theme";

    $bible-popover-theme-color: $gray-600;

    .bible-popover {
        background: white;
        font-family: Arial;
        position: fixed;
        line-height: 120%;
        color: black;
        border: 1px solid $bible-popover-theme-color;
        font-size: 13px;
        z-index: 1000;
        border-radius: .5em;
        overflow: hidden;
        box-shadow: 0 0 3px rgba(0, 0, 0, 0.75);

        header {
            background: $bible-popover-theme-color;
            color: white;
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            font-size: 1.2em;
            line-height: 1.2em;
            height: 1.5em;
            box-shadow: 0 0 3px black;
            z-index: 10;


            h1 {
                font-size: 1em;
                font-weight: bold;
                cursor: move;
            }

            .header-left {
                padding-left: .5em;
                cursor: move;

                .cursorMove {
                    cursor: move;
                }

                .text {
                    display: inline-block;
                    padding-top: .2em;
                }
            }

            .action {
                display: inline-block;
                outline: none;
                transition: 0.2s;
                line-height: 1em;
                font-size: 1em;
                color: white;
                cursor: default;
                border-radius: 1em;

                svg {
                    display: inline-block;
                    width: 1.5em;
                    height: 1.5em;
                    font-size: 1em;
                    stroke-width: 1;
                    stroke: currentColor;
                    fill: currentColor;
                    padding: .2em 0;
                }

                &.enabled:hover {
                    background: white;
                    cursor: pointer;
                    color: $bible-popover-theme-color;
                }

            }

            .header-actions {
                float: right;

                .previous, .next, .pin, .close {
                    opacity: 0.5;

                    &.enabled {
                        opacity: 1;
                    }

                    &:hover {
                        transition: 0.2s;
                    }

                }

                .close {
                    margin-right: .2em;
                }

                .space {
                    display: inline-block;
                    width: 1em;
                }
            }
        }


        .bible-popover-content {
            position: absolute;
            top: 1.75em;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: auto;
            -webkit-overflow-scrolling: touch;


            .article-inner {
                display: inline-block;
                padding: 0 10px 10px 10px;
                width: 100%;
            }

            .float-right {
                float: right;
            }

            a {
                color: $bible-popover-theme-color;
                text-decoration: underline;
                font-weight: normal;
                cursor: pointer;
            }

            a:hover {
                text-decoration: none;
            }

            p {
                margin-bottom: 5px;
                text-align: justify;
            }

            h2 {
                font-size: inherit;
                font-weight: bold;
                margin: 10px 0 5px 0;
            }

            .action-button {
                background: white;
                color: black;
                border: 1px solid $bible-popover-theme-color;
                border-radius: 0.3em;
                text-decoration: none;
                padding: 2px 4px 1px 4px;
                font-size: 0.85em;
                position: relative;
                margin-right: 5px;
                top: -1px;
                opacity: 0.9;
                transition: 0.2s;
            }

            .action-button:hover {
                background: $bible-popover-theme-color;
                color: white;
                opacity: 1;
            }

            .action-button + .action-button {
                margin-right: 0;
            }

            .ref-actions {
                float: right;
            }

            .vno {
                font-size: 11px;
                color: $bible-popover-theme-color;
                position: relative;
                top: -2px;

            }

            .version {
                margin: 10px 0 3px 0;
                font-size: 11px;
                color: black;

                & a, span {
                    opacity: 0.65;
                }

                a {
                    color: inherit;
                    text-decoration: none;
                    border-bottom: 1px dotted black;
                    transition: 0.3s;

                    &:hover {
                        opacity: 1;
                        border-bottom-style: solid;
                    }
                }
            }
        }

    }


</style>