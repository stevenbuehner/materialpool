<template>
    <hollow-dots-spinner :dot-size="10"
                         :dots-num="3"
                         :animation-duration="1500"
                         color="grey"
                         class="materialpool-spinner"/>
</template>

<script>
	import HollowDotsSpinner from "epic-spinners/src/components/lib/HollowDotsSpinner";

	export default {
		name: "materialpool-spinner",
		components: {HollowDotsSpinner}
	}
</script>

<style lang="scss">
    .materialpool-spinner {
        display: inline-block;
    }
</style>