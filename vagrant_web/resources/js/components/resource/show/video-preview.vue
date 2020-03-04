<template>
    <div class="card-header sbVideo ">
        <video ref="videoPlayer"
               class="video-js vjs-theme-sea"
               data-setup='{"fluid": true}'>
        </video>
    </div>
</template>

<script>

	import {poolResourceMediastream, previewImageFirstPage} from "../../serverRoutes";
	import resourcePreviewZoom                              from '../resource-preview-zoom';
	import resourcePreview                                  from '../resource-preview.mixin';
	import resourceLinks                                    from "../resource-links.mixin";
	import videojs                                          from 'video.js';

	export default {
		name: 'VideoPreview',

		mixins: [resourcePreviewZoom, resourcePreview, resourceLinks],

		data() {
			return {
				player: null,
			};
		},

		computed: {
			options() {
				return {
					autoplay: false,
					controls: true,
					sources: this.videoSources,
					poster: this.posterRoute,
				};
			},
			videoSources() {
				return [
					{
						src: this.videoRoute,
						type: this.videoMimeType
					}
				];
			},

			posterRoute() {
				return previewImageFirstPage(this.resource);
			},

			videoRoute() {
				return poolResourceMediastream(this.resource);
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


		mounted() {
			this.player = videojs(this.$refs.videoPlayer, this.options, function onPlayerReady() {
				// console.log('onPlayerReady', this);
			})
		},

		beforeDestroy() {
			if (this.player) {
				this.player.dispose()
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

<style type="text/scss">
    @import "~video.js/dist/video-js.css";
    @import "~@videojs/themes/dist/sea/index.css";

    .sbVideo {
    }
</style>