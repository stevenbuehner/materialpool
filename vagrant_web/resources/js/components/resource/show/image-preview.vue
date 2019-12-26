<template>
    <div class="mb-2 myCard" :title="title"
         :img-src="resourceImagePreviewUrl"
         @click="goToResource">

        <img class="card-img-top" :src="resourceImagePreviewUrl" img-alt="Preview Image"/>

        <div class="card-body">

            <h4 class="card-title" v-if="resource.notes && resource.notes.length <= 3">{{title}}</h4>

            {{resource.notes}}

        </div>


    </div>

</template>

<script>

	import {BCard}                 from 'bootstrap-vue'
	import {BButton}               from 'bootstrap-vue'
	import resourceLinks           from '../resource-links.mixin';
	import {previewImageFirstPage} from "../../serverRoutes";

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
					default: 300
				},
				height: {
					required: false,
					default: 300
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
			goToResource() {
				this.$router.push({
					name: 'resource-detail',
					params: {id: this.resource.id}
				});
			}
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