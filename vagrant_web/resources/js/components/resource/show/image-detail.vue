<template>

    <b-img-lazy
            :src="resourceImagePreviewUrl"
            fluid
            :alt="resource.notes"
            center
            @click="goToResource"></b-img-lazy>

</template>

<script>

	import {BImgLazy}                                           from 'bootstrap-vue';
	import {BCard}                                              from 'bootstrap-vue'
	import {BButton}                                            from 'bootstrap-vue'
	import {previewImageFirstPage}                              from '../../serverRoutes';
	import resourceLinks                                        from '../resource-links.mixin';
	import {max_preview_image_size_x, max_preview_image_size_y} from "../../../apps/config";

	export default {

		mixins: [resourceLinks],

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
				var title = 'Resource';

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
			goToResource() {
				window.location.href = this.resourceUrl;
			}
		},
		components: {
			BCard,
			BButton,
			BImgLazy
		}
	}
</script>

<style scoped>
    .myCard {
        max-width: 20rem;
        cursor: pointer;
    }
</style>