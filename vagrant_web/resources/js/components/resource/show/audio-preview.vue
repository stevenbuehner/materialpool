<template>
    <div class="card-header">
        <span v-if="resource.original_filename && hovered"><i>{{resource.original_filename}}</i></span>
        No audio Preview (go to Audio Details)
    </div>
</template>

<script>
	import resourcePreview from '../resource-preview.mixin';

	export default {
		mixins: [resourcePreview],
	}
</script>

<style scoped>

</style>