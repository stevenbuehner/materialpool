<template>
    <div class="page-list row">
        <page
                v-for="page in pages"
                :index="page.index"
                :image="page.image"
                :isSelectable="page.isSelectable"
                :isSelected="page.isSelected"
                :assignedMaterials="page.assignedMaterials"
                :key="page.index"
                @firstPageSelected="firstPageSelected"
                @lastPageSelected="lastPageSelected"
                @addPageSelection="addPageSelection"
        >Page {{page.label}}
        </page>
    </div>
</template>

<script>

    import Page from './page.vue';

    export default {

        props: {
            resource: {
                type: Object,
                required: true,
            },
            previewLinkPattern: {
                type: String,
                required: false,
                default: '/pdfpreview/res-{id}/page-{page}'
            }
        },

        data: function () {

            let sel = {};

            // Init selectedPages to watch for
            for (let i = 1; i <= this.pageCount; i++) {
                sel[i] = false;
            }

            return {
                selectedPages: sel
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
                        image: this.previewLinkPattern.replace('{id}', this.fileId).replace('{page}', i)
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
            }

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
                this.$set(this.selectedPages, pageIndex, !this.selectedPages[pageIndex]);

            },

        },

        components: {
            Page
        }

    }
</script>

<style scoped>
</style>