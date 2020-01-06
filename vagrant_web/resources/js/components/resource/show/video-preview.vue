<template>
    <div class="card-header">
        <video
                class="sbVideo "
                controls preload="auto"
                data-setup='{"fluid": true}'
                :poster="posterRoute">

            <source :src="videoRoute" :type="videoMimeType"/>
        </video>
    </div>
</template>

<script>

	import {poolResourceVideostream, previewImageFirstPage} from "../../serverRoutes";
	import resourcePreviewZoom                              from '../resource-preview-zoom';

	export default {
		mixins: [resourcePreviewZoom],

		props: {
			resource: {
				required: true,
				type: Object
			},
		},


		computed: {
			posterRoute() {
				return previewImageFirstPage(this.resource);
			},

			videoRoute() {
				return poolResourceVideostream(this.resource);
			},

			videoMimeType() {

				let type = 'video';
				switch (this.resource.mime_type) {
					case 'video/quicktime':
						type = 'video/mp4';
						break;
					case undefined:
						break;
					default:
						type = this.resource.mime_type;
				}

				return type;
			}
		},

		methods: {
			_getPreviewZoomImagesAndTitles() {
				return [{
					title: this.resource.notes || 'Video',
					src: this.posterRoute
				}];
			},
		}


	}
</script>

<style scoped>
    .sbVideo {
        max-width: 100%;
    }
</style>