<template>
    <div>
        <div class="previewContainer" @click="$refs.imageZoom.show(currentlyDisplayedPageIndex)">
            <b-image :src="currentlyDisplayedImage.src"
                     :alt="currentlyDisplayedImage.title"
                     :key="currentlyDisplayedImage.src"
                     class="card-img-top"></b-image>

            <span class="previous"
                  @click.stop="previousPreviewImage"
                  v-if="previewLimitedImages.length > 1"><</span>
            <span class="next"
                  @click.stop="nextPreviewImage"
                  v-if="previewLimitedImages.length > 1">></span>
            <div class="label">
                {{currentlyDisplayedImage.title}}
                <div v-if="previewLimitedImages.length < pageCount"
                     class="limitedPreview"
                >({{previewPhrase}})
                </div>
            </div>
        </div>
        <span v-if="pageCount === 0">Seitenangabe fehlt</span>

        <image-zoom :data="previewLimitedImages" ref="imageZoom"></image-zoom>
    </div>
</template>

<script>

    import {pdfPreviewImageForPage} from './../../serverRoutes';
    import bImage from 'bootstrap-vue/src/components/image/img';
    import pdfMixin from './../pdf-mixin';
    import ImageZoom from "../../modals/imageZoom";

    export default {
        mixins: [pdfMixin],

        props: {
            resource: {
                required: true,
                type: Object
            },
            maxPreviewPages: {
                required: false,
                type: Number,
                default: 5
            }
        },

        data() {
            return {
                currentlyDisplayedPageIndex: 0
            };
        },
        computed: {

            currentlyDisplayedImage() {
                return this.previewLimitedImages[this.currentlyDisplayedPageIndex];
            },

            previewPageNumbers() {

                let pages = [];

                if (this.pagePivotCount) {
                    for (let i in this.resource.pivot.limitation.pages) {
                        pages.push(this.resource.pivot.limitation.pages[i]);
                    }
                } else if (this.pageCount === 0) {
                    pages.push(1);
                } else if (this.pageCount > 0) {
                    for (let i = 1; i <= this.pageCount; i++) {
                        pages.push(i);
                    }
                }

                return pages;

            },

            previewLimitedImages() {

                let urls = [];

                for (let i in this.previewPageNumbers) {
                    if (i < this.maxPreviewPages) {
                        urls.push(this.getPreviewImage(this.previewPageNumbers[i]));
                    } else {
                        break;
                    }
                }

                return urls;

            },

            previewPhrase() {

                if (this.previewPageNumbers.length > this.maxPreviewPages) {
                    return this.$t('pool.only-limited-pages', {
                        COUNT: this.previewLimitedImages.length,
                        SUM: this.previewPageNumbers.length
                    })
                } else {
                    return this.$t('pool.limited-pages', {
                        COUNT: this.previewLimitedImages.length,
                        SUM: this.previewPageNumbers.length
                    })
                }

                // (Preview nur {{previewLimitedImages.length}}/{{previewPageNumbers.length}} Seiten)
            }
        },

        methods: {

            getPreviewImage(pageNo) {
                return {
                    src: pdfPreviewImageForPage(this.resource, pageNo),
                    title: this.$t('pool.Page') + ' ' + pageNo,
                    page_no: pageNo
                }
            },

            previousPreviewImage() {
                if (this.currentlyDisplayedPageIndex === 0) {
                    this.currentlyDisplayedPageIndex = this.previewLimitedImages.length - 1;
                } else {
                    this.currentlyDisplayedPageIndex--;
                }
            },

            nextPreviewImage() {
                if (this.previewLimitedImages.length <= this.currentlyDisplayedPageIndex + 1) {
                    this.currentlyDisplayedPageIndex = 0;
                } else {
                    this.currentlyDisplayedPageIndex++;
                }
            }
        },

        components: {
            ImageZoom,
            bImage
        }

    }
</script>

<style scoped>

    .previewContainer {
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .previous, .next {
        position: absolute;
        background-color: rgba(255, 255, 255, 0.5);
        color: black;
        font-size: 2em;
        top: 50%;
        cursor: pointer;
    }

    .previous:hover, .next:hover {
        background-color: rgba(255, 255, 255, 1);
    }

    .previous {
        left: 0.25em;
    }

    .next {
        right: 0.25em;
    }

    .label {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        text-align: center;
        padding: .25em .5em .25em .5em;
        background-image: linear-gradient(rgba(255, 255, 255, 0.85), #ffffff);
    }

    .limitedPreview {
        font-size: smaller;
    }

</style>