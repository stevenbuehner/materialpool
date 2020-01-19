<template>
    <div class="card-header">
        <div>{{resource.content | trim(300) }}</div>
    </div>
</template>

<script>
	import truncateFilter  from '../../../filters/truncate-filter.mixin';
	import resourcePreview from '../resource-preview.mixin';

	export default {
		mixins: [
			truncateFilter,
			resourcePreview
		],
	}
</script>

<style scoped>

</style>