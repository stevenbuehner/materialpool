<template>
    <div class="container-fluid">
        <div class="sbResourceNewestList row" v-if="resources.length > 0">
            <div class="col col-xl-2 col-md-3 col-sm-4 col-6 pb-4" v-for="r in resources">
                <resource-preview :resource="r"/>
            </div>
        </div>

        <div class="alert alert-info" v-if="isLoading">
            {{$t('pool.Loading-resource')}}
        </div>

        <b-pagination-nav
                v-if="total > 0"
                v-model="page"
                :limit="10"
                :number-of-pages="numPages"
                use-router
                :link-gen="linkGeneration"
                align="center">
        </b-pagination-nav>
    </div>
</template>

<script>
	import {BButton, BPaginationNav} from 'bootstrap-vue';
	import ResourcePreview           from "../../../components/resource/show/resource-preview";
	import {savingDialogs}           from "../../../helper/flashMessages";

	export default {
		name: "ResourceNewest",

		mixins: [savingDialogs],

		data() {
			return {
				page: 1,
				numPages: 1,
				total: 1,

				isLoading: true,
				refreshResources: 0,
			}
		},


		asyncComputed: {
			resources: {
				get() {
					this.isLoading = true;

					return this.$store.dispatch('resources/find', {
						order_by: 'id',
						order_dir: 'desc',
						page: this.page
					})
					           .then(({data, current_page, last_page, total}) => {
						           this.page      = current_page;
						           this.numPages  = last_page;
						           this.total     = total;
						           this.isLoading = false;
						           return data;
					           })
					           .catch((message) => {
						           this.flashActionFailed(message);
					           });
				},
				watch() {
					this.refreshResources;
				},
				default: []
			}
		},

		methods: {
			linkGeneration(pageNum) {
				return {
					name: 'resource-lonely',
					query: {
						page: pageNum
					}
				}
			},
		},

		components: {
			ResourcePreview,
			BPaginationNav,
			BButton
		}
	}
</script>

<style scoped>
    img {
        width: auto;
        height: auto;
        max-height: 20rem;
    }
</style>