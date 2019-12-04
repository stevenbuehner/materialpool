<template>
    <div class="row">
        <div v-for="(image, index) in previewImages"
             class="col-lg-3 col-md-4 col-sm-6 col-12 imageContainer pdfDetail img-thumbnail"
             @click="$refs.imageZoom.show(index)"
             :key="image.src">
            <b-img-lazy
                    v-if="index > 12"
                    :src="image.src"
                    :alt="image.title"
                    fluid
            ></b-img-lazy>
            <b-img
                    v-if="index <= 12"
                    :src="image.src"
                    :alt="image.title"
                    fluid
            ></b-img>

            <div class="title text-center">{{image.title}}</div>
        </div>

        <image-zoom :data="previewImages" ref="imageZoom"></image-zoom>
    </div>
</template>

<script>

    import {previewImageFirstPage} from '../../serverRoutes';
    import {BImg} from 'bootstrap-vue';
    import {BImgLazy} from 'bootstrap-vue';
    import pdfMixin from '../pdf-mixin';
    import ImageZoom from "../../modals/imageZoom";

    export default {
        mixins: [pdfMixin],

        props: {
            resource: {
                required: true,
                type: Object
            },
        },

        data() {
            return {};
        },
        computed: {

            currentlyDisplayedImage() {
                return this.previewImages[this.currentlyDisplayedPageIndex];
            },

            previewImages() {

                let urls = [];

                if (this.pageCount === 0) {
                    urls.push({
                        src: previewImageFirstPage(this.resource),
                        title: 'Startseite',
                        page_no: 1
                    });
                } else if (this.pageCount > 0) {

                    urls = this.previewablePages.map((pageNo) => {
                        return this.generatePreviewObject(this.resource, pageNo);
                    });
                }

                return urls;
            }
        },

        methods: {},

        components: {
            ImageZoom,
            BImg,
            BImgLazy
        }

    }
</script>

<style type="scss">

    .pdfDetail.imageContainer {

        cursor: pointer;

        .title {
            font-size: 0.75em;
        }
    }


</style>