<template>
    <div class="card-header">
        <span v-if="resource.original_filename && hovered"><i>{{resource.original_filename}}</i></span>
        <img class="card-img-top" :src="resourceImagePreviewUrl" alt="Preview Image"/>
    </div>
</template>

<script>
	import resourcePreview         from '../resource-preview.mixin';
	import {previewImageFirstPage} from "../../serverRoutes";

	export default {
		mixins: [resourcePreview],

		computed: {

			resourceImagePreviewUrl() {
				return previewImageFirstPage(this.resource, this.width, this.height);
			},
		}
	}
</script>

<style scoped>

</style>