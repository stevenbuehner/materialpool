<template>
    <div class="row">
        <div v-for="image in previewImages"
             class="col-lg-3 col-md-4 col-sm-6 col-1 imageContainer img-thumbnail"
             @click="showModalImage(image)">
            <b-image-lazy
                    :src="image.src"
                    :alt="image.title"
                    :key="image.src"
                    fluid
            ></b-image-lazy>

            <div class="title text-center">{{image.title}}</div>
        </div>

        <b-modal ref="imageZoomModal"
                 centered
                 hide-footer
                 lazy
                 :title="detailImage.title"
                 size="lg">
            <b-image :src="detailImage.src"
                     fluid
                     @click="$refs.imageZoomModal.hide()"></b-image>
        </b-modal>

    </div>
</template>

<script>

    import {pdfPreviewImageFirstPage} from './../serverRoutes';
    import bImage from 'bootstrap-vue/src/components/image/img';
    import bImageLazy from 'bootstrap-vue/src/components/image/img-lazy';
    import pdfMixin from './pdf-mixin';
    import bModal from 'bootstrap-vue/src/components/modal/modal';

    export default {
        mixins: [pdfMixin],

        props: {
            resource: {
                required: true,
                type: Object
            },
        },

        data() {
            return {
                detailImage: pdfPreviewImageFirstPage(this.resource, 1)
            };
        },
        computed: {

            currentlyDisplayedImage() {
                return this.previewImages[this.currentlyDisplayedPageIndex];
            },

            previewImages() {

                let urls = [];

                if (this.pageCount === 0) {
                    urls.push({
                        src: pdfPreviewImageFirstPage(this.resource),
                        title: 'Startseite',
                        page_no: 1
                    });
                } else if (this.pageCount > 0) {
                    for (let i = 1; i <= this.pageCount; i++) {
                        urls.push(this.generatePreviewObject(this.resource, i));
                    }
                }

                return urls;
            }
        },

        methods: {

            showModalImage(image) {
                this.detailImage = image;
                this.$refs.imageZoomModal.show();
            }
        },

        components: {
            bImage,
            bImageLazy,
            bModal
        }

    }
</script>

<style scoped>

    .title {
        font-size: 0.75em;
    }

</style>