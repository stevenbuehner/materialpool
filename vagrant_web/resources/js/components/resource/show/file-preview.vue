<template>
    <div class="card-header">
        <span v-if="resource.original_filename"><i>{{resource.original_filename}}</i></span>
        <span v-else>No file preview available</span>
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