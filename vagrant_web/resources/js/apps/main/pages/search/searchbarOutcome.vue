<template>
    <div>
        <material-listing
                :materialIds="materialIds"
                :highlight-keywords="highlightKeywords"
                :highlight-bibleverses="highlightBibleverses"
        ></material-listing>
    </div>
</template>

<script>

	import MaterialListing from '../../../../components/Material/MaterialListing.vue';

	export default {

		props: {
			materialIds: {
				required: true,
				type: Array
			},

			highlightKeywords: {
				type: Array,
				required: false,
				default() {
					return [];
				}
			},

			highlightBibleverses: {
				type: Array,
				required: false,
				default() {
					return [];
				}
			},

		},

		components: {
			MaterialListing
		}
	}
</script>

<style scoped>

</style>