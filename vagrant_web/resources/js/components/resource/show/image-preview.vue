<template>
    <div class="mb-2 myCard" :title="title"
         :img-src="resourceImagePreviewUrl"
         @click="_emitPreviewZoomRequest">

        <img class="card-img-top" :src="resourceImagePreviewUrl" img-alt="Preview Image"/>

        <div class="card-body">

            <h4 class="card-title" v-if="resource.notes && resource.notes.length <= 3">{{title}}</h4>

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
	import {max_preview_image_size_x, max_preview_image_size_y} from "../../../apps/config";

	export default {

		name: 'imagePreview',

		mixins: [resourceLinks, resourcePreviewZoom],

		props:
			{
				resource: {
					required: true,
					type: Object
				},
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