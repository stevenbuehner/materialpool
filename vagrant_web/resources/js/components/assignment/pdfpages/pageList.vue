<template>
    <div class="row">
        <page
                v-for="(page, index) in pages"
                :index="page.index"
                :image="page.image"
                :isSelectable="page.isSelectable"
                :isSelected="page.isSelected"
                :assignedMaterials="page.assignedMaterials"
                :key="page.index"
                @firstPageSelected="firstPageSelected"
                @lastPageSelected="lastPageSelected"
                @addPageSelection="addPageSelection"
                @zoomInRequest="showZoom(index)"
                :class="pageSizeClass"
        >Page {{page.label}}
        </page>

        <b-modal ref="imageZoomModal"
                 centered
                 hide-footer
                 lazy
                 :title="zoomedImage.title"
                 :hide-header-close="true"
                 size="lg"
                 class="zoomImageModal">
            <b-image :src="zoomedImage.src"
                     fluid
                     @click="hideZoom()"></b-image>
            <span class="previous"
                  @click.prevent="showZoom(zoomedImage.previous)"
                  v-if="zoomedImage.previous >= 0"><</span>
            <span class="next"
                  @click.prevent="showZoom(zoomedImage.next)"
                  v-if="zoomedImage.next >= 0">></span>
            <template slot="modal-header" v-if="zoomedImage.current >= 0">
                <div class="checked-modal-page"
                     :class="{selected: pages[zoomedImage.current].isSelected}"
                     @click="addPageSelection(pages[zoomedImage.current].index)">
                    <span v-if="selectedPagesArray.length > 0">selected pages: {{selectedPagesArray.join(', ')}}</span>
                    <span v-if="selectedPagesArray.length === 0">click to select first page</span>
                </div>
            </template>
        </b-modal>
    </div>
</template>

<script>

    import Page from './page.vue';
    import bModal from 'bootstrap-vue/src/components/modal/modal';
    import bImage from 'bootstrap-vue/src/components/image/img'
    import {pdfPreviewImageForPage} from "../../serverRoutes";

    export default {

        props: {
            resource: {
                type: Object,
                required: true,
            },
            previewSize: {
                type: String,
                required: false,
                default: 'md'
            },
        },

        data: function () {

            let sel = {};

            // Init selectedPages to watch for
            for (let i = 1; i <= this.resource.page_count; i++) {
                sel[i] = false;
            }

            return {
                selectedPages: sel,

                zoomedImage: {
                    title: '',
                    src: '',
                    current: -1,
                    next: -1,
                    previous: -1,
                }

            };
        },

        computed: {

            fileId() {
                return this.resource.id;
            },

            pageCount() {
                return this.resource.page_count;
            },

            pages() {
                let pages = [];

                for (let i = 1; i <= this.pageCount; i++) {
                    pages.push({
                        index: i,
                        label: i,
                        isSelectable: true,
                        isSelected: this.selectedPages[i],
                        assignedMaterials: this.resource.materials.reduce((total, mat) => {
                            if (mat.pivot && mat.pivot.limitation && mat.pivot.limitation.pages && Array.isArray(mat.pivot.limitation.pages)) {
                                if (mat.pivot.limitation.pages.includes(i)) {
                                    return total + 1;
                                } else {
                                    return total;
                                }
                            } else {
                                return total + 1;
                            }
                        }, 0),
                        image: pdfPreviewImageForPage(this.resource, i)
                    })
                }

                return pages;
            },

            selectedPagesArray() {
                const res = [];

                for (let i in this.selectedPages) {
                    if (this.selectedPages[i] === true) {
                        res.push(parseInt(i));
                    }
                }

                return res;
            },


            pageSizeClass() {

                let myClass = 'col-12 col-sm-6 col-md-3 col-lg-3 col-xl-2';

                switch (this.previewSize) {
                    case 'xs':
                    case 'sm':
                        myClass = 'col-6 col-sm-4 col-md-3 col-lg-3 col-xl-2';
                        break;
                    case 'md':
                        myClass = 'col-12 col-sm-6 col-md-4 col-lg-3 col-xl-3';
                        break;
                    case 'lg':
                    case 'xl':
                    default:
                        myClass = 'col-12 sm-12 col-md-6 col-lg-4 col-xl-4';
                        break;
                }

                return myClass;
            },

        },

        watch: {
            'selectedPages': {
                handler: function (newVal, oldVal) {
                    // console.log('Selection changed', newVal);

                    this.$emit('page-selection-updated', this.selectedPagesArray);
                },
                deep: true,
                immediate: true
            }
        },


        methods: {
            firstPageSelected: function (pageIndex) {
                for (let i = 1; i <= this.pageCount; i++) {
                    // this.selectedPages[i] = pageIndex === i;
                    this.$set(this.selectedPages, i, pageIndex === i);
                }
            },


            lastPageSelected: function (pageIndex) {

                let firstSelectedPage = 1;

                // Start looking from the pageIndex backwards
                for (let i = pageIndex; i >= 1; i--) {
                    if (this.selectedPages[i] === true) {
                        break;
                    } else {
                        firstSelectedPage = i;
                    }
                }

                // If nothing was found before the pageIndex, look also forwards
                if (firstSelectedPage === 1 && this.selectedPages[1] === false) {
                    for (let i = pageIndex + 1; i <= this.pageCount; i++) {
                        if (this.selectedPages[i] === true) {
                            firstSelectedPage = i;
                            break;
                        }
                    }
                }


                const lastSelectedPage = pageIndex;
                const min              = Math.min(lastSelectedPage, firstSelectedPage);
                const max              = Math.max(lastSelectedPage, firstSelectedPage);

                for (let i = min; i <= max; i++) {
                    this.$set(this.selectedPages, i, true);
                    // this.selectedPages[i] = true;
                }

            },

            addPageSelection: function (pageIndex) {

                // toggle selection
                // this.selectedPages[pageIndex] = !this.selectedPages[pageIndex];
                console.log('Set index: ', pageIndex, !this.selectedPages[pageIndex]);

                this.$set(this.selectedPages, pageIndex, !this.selectedPages[pageIndex]);

            },


            selectAllPages() {
                for (let i in this.selectedPages) {
                    this.selectedPages[i] = true;
                }
            },

            clearAllPages() {
                for (let i in this.selectedPages) {
                    this.selectedPages[i] = false;
                }
            },

            showZoom(arrayIndex) {

                if (this.pages[arrayIndex]) {
                    this.zoomedImage.src     = this.pages[arrayIndex].image;
                    this.zoomedImage.current = arrayIndex;

                    if (this.pages[arrayIndex + 1]) {
                        this.zoomedImage.next = arrayIndex + 1;
                    } else {
                        this.zoomedImage.next = -1;
                    }


                    if (this.pages[arrayIndex - 1]) {
                        this.zoomedImage.previous = arrayIndex - 1;
                    } else {
                        this.zoomedImage.previous = -1;
                    }

                    this.$refs.imageZoomModal.show();
                }


            },

            hideZoom() {

                this.$refs.imageZoomModal.hide();

            },

        },

        components: {
            Page,
            bModal,
            bImage,
        }

    }
</script>

<style>
    .zoomImageModal header.modal-header {
        padding: 0;
    }
</style>

<style scoped>

    .previous, .next {
        position: absolute;
        color: black;
        font-size: 2em;
        height: 100%;
        top: 0;
        cursor: pointer;
        display: flex;
        justify-content: center;
        flex-direction: column;
    }

    .previous {
        left: 0;
        padding: 0 1em 0 .75em;
    }

    .next {
        right: 0;
        padding: 0 .75em 0 1em;
    }

    .previous:hover {
        background-image: linear-gradient(to right, rgb(184, 184, 184), rgba(210, 210, 210, 0.05));
    }

    .next:hover {
        background-image: linear-gradient(to left, rgb(184, 184, 184), rgba(210, 210, 210, 0.05));
    }

    .checked-modal-page, .checked-modal-page.selected:hover {
        padding: 1rem;
        text-align: center;
        background-color: #ed7a76;
        box-shadow: 0 0 0.5em #edc2b4;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    .checked-modal-page.selected, .checked-modal-page:hover {
        background-color: #709aed;
        box-shadow: 0 0 0.5em #8eaeed;
    }


</style>