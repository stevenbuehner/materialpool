<template>
    <div>
        <div class="previewContainer">
            <b-image :src="currentlyDisplayedImage.src"
                     :alt="currentlyDisplayedImage.title"
                     :key="currentlyDisplayedImage.src"
                     class="card-img-top"></b-image>

            <span class="previous"
                  @click.prevent="previousPreviewImage"
                  v-if="previewImages.length > 1"><</span>
            <span class="next"
                  @click.prevent="nextPreviewImage"
                  v-if="previewImages.length > 1">></span>
            <div class="label">
                {{currentlyDisplayedImage.title}}
                <div
                        v-if="previewImages.length < pageCount"
                        class="limitedPreview"
                >(Preview nur {{previewImages.length}}/{{previewPageNumbers.length}} Seiten)
                </div>
            </div>
        </div>
        <span v-if="pageCount === 0">Seitenangabe fehlt</span>
    </div>
</template>

<script>

    import {pdfPreviewImageFirstPage} from './../../serverRoutes';
    import bImage from 'bootstrap-vue/src/components/image/img';
    import pdfMixin from './../pdf-mixin';

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
                return this.previewImages[this.currentlyDisplayedPageIndex];
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

            previewImages() {

                let urls = [];

                for (let i in this.previewPageNumbers) {
                    if (i < this.maxPreviewPages) {
                        urls.push({
                            src: pdfPreviewImageFirstPage(this.resource),
                            title: this.$t('pool.Page') + ' ' + this.resource.pivot.limitation.pages[i],
                            page_no: this.resource.pivot.limitation.pages[i]
                        });
                    } else {
                        break;
                    }
                }

                return urls;

            }
        },

        methods: {

            previousPreviewImage() {
                if (this.currentlyDisplayedPageIndex === 0) {
                    this.currentlyDisplayedPageIndex = this.previewImages.length - 1;
                } else {
                    this.currentlyDisplayedPageIndex--;
                }
            },

            nextPreviewImage() {
                if (this.previewImages.length <= this.currentlyDisplayedPageIndex + 1) {
                    this.currentlyDisplayedPageIndex = 0;
                } else {
                    this.currentlyDisplayedPageIndex++;
                }
            }
        },

        components: {
            bImage
        }

    }
</script>

<style scoped>

    .previewContainer {
        position: relative;
        overflow: hidden;
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
        text-align: center;
        padding: 1em;
        background-color: rgba(255, 255, 255, 1);
    }

    .limitedPreview {
        font-size: smaller;
    }

</style>