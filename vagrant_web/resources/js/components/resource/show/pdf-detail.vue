<template>
    <div>
        <div class="row" v-if="maxPagesToDisplay < pagePivotCount">
            <div class="col">
                <b-form-select
                        v-model="maxPagesToDisplay"
                        :options="displayPagesLimitOptionsFormated"
                />
            </div>
        </div>
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

            <div class="col-12 col-sm-6 col-md-4 col-lg-4 col-xl-3 p-1 imageContainer "
                 v-if="pagePivotCount > maxPagesToDisplay">
                <div class="moreImages d-flex justify-content-center align-items-center">
                    <div>
                        <div class="moreDots">...</div>
                        <div class="title text-center">{{$t('pool.more-pages-available')}}</div>
                    </div>
                </div>
            </div>

            <image-zoom :data="previewImages" ref="imageZoom"></image-zoom>
        </div>
    </div>

</template>

<script>

	import {previewImageFirstPage} from '../../serverRoutes';
	import {BImg}                  from 'bootstrap-vue';
	import {BImgLazy}              from 'bootstrap-vue';
	import {BFormSelect}           from 'bootstrap-vue';
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
			return {
				maxPagesToDisplay: 50,
			};
		},
		computed: {

			currentlyDisplayedImage() {
				return this.previewImages[this.currentlyDisplayedPageIndex];
			},

			previewImages() {

				let urls = [];

				if (this.pagePivotCount === 0) {
					urls.push({
						src: previewImageFirstPage(this.resource),
						title: 'Startseite',
						page_no: 1
					});
				} else if (this.pagePivotCount > 0) {

					urls = this.previewablePages.splice(0, Math.min(this.maxPagesToDisplay, this.previewablePages.length)).map((pageNo) => {
						return this.generatePreviewObject(this.resource, pageNo);
					});
				}

				return urls;
			},

			displayPagesLimitOptionsFormated() {
				return this.displayPagesLimitOptions.map((el) => {
					return {value: el, text: el};
				})
			},

			displayPagesLimitOptions() {
				// 50 ist Standard und sollte in jeder Auswahl vorhanden sein!

				if (this.pagePivotCount <= 100) {
					return [20, 30, 40, 50, 70, 80, 100];
				} else if (this.pagePivotCount <= 500) {
					return [20, 50, 100, 300, 500];
				} else if (this.pagePivotCount <= 1000) {
					return [20, 50, 200, 400, 600, 1000];
				} else {
					return [20, 50, 200, 400, 600, 1000, 10000];
				}
			}
		},

		methods: {},

		components: {
			ImageZoom,
			BImg,
			BImgLazy,
			BFormSelect
		}

	}
</script>

<style type="scss">

    @import "resources/sass/theme";

    .pdfDetailWrapper {
        margin: 0;

        .imageContainer {


            .oneImagePage, .moreImages {

                background-color: $card-bg;
                cursor: pointer;

                border: $border-width solid $border-color;
                border-radius: $card-border-radius;

                .title {
                    font-size: 0.75em;
                }

                .moreDots {
                    font-size: 5em;
                    line-height: 1em;
                    color: grey;
                }

                &.moreImages {
                    cursor: default;
                    text-align: center;
                    height: 100%;
                    background-color: $card-bg;
                }
            }
        }

    }

</style>