<template>
    <div class="myCard" :title="title"
         :class="{'mb-2' : hovered}"
         :img-src="resourceImagePreviewUrl"
         @click="_emitPreviewZoomRequest">

        <img class="card-img-top" :src="resourceImagePreviewUrl" alt="Preview Image"/>

        <div class="card-body" v-if="resource.notes && resource.notes.length >= 3">
            {{resource.notes}}
        </div>


    </div>

</template>

<script>

	import {BCard}                                              from 'bootstrap-vue'
	import {BButton}                                            from 'bootstrap-vue'
	import resourceLinks                                        from '../resource-links.mixin';
	import {previewImageFirstPage}                              from "../../serverRoutes";
	import resourcePreviewZoom                                  from '../resource-preview-zoom';
	import resourcePreview                                      from '../resource-preview.mixin';
	import {max_preview_image_size_x, max_preview_image_size_y} from "../../../apps/config";

	export default {

		name: 'ImagePreview',

		mixins: [resourceLinks, resourcePreviewZoom, resourcePreview],

		props: {
			width: {
				required: false,
				default: max_preview_image_size_x
			},
			height: {
				required: false,
				default: max_preview_image_size_y
			}
		},

		computed: {

			title() {
				let title = 'Resource';

				if (this.resource.original_filename) {
					title = this.resource.original_filename;
				}

				return title
			},

			resourceImagePreviewUrl() {
				return previewImageFirstPage(this.resource, this.width, this.height);
			},

		},

		methods: {
			_getPreviewZoomImagesAndTitles() {
				return [{
					src: previewImageFirstPage(this.resource),
					title: this.resource.notes || this.resource.original_filename || ''
				}];
			},
		},

		components: {
			BCard,
			BButton
		}
	}
</script>

<style scoped>
    .myCard {
        max-width: 20rem;
        cursor: pointer;
    }
</style>