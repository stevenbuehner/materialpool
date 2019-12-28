<template>
    <div class="row m-n1 pdfDetailWrapper">
        <div v-for="(image, index) in previewImages"
             class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3 p-1 imageContainer"
             @click="$refs.imageZoom.show(index)"
             :key="image.src">
            <div class="oneImagePage">
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
        </div>

        <image-zoom :data="previewImages" ref="imageZoom"></image-zoom>
    </div>
</template>

<script>

	import {previewImageFirstPage} from '../../serverRoutes';
	import {BImg}                  from 'bootstrap-vue';
	import {BImgLazy}              from 'bootstrap-vue';
	import pdfMixin                from '../pdf-mixin';
	import ImageZoom               from "../../modals/imageZoom";

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

    @import "resources/sass/theme";

    .pdfDetailWrapper {
        margin: 0;

        .imageContainer {

            .oneImagePage {

                background-color: $card-bg;
                cursor: pointer;

                border: $border-width solid $border-color;
                border-radius: $card-border-radius;


                .title {
                    font-size: 0.75em;
                }
            }

        }
    }

</style>