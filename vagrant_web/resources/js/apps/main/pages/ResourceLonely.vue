<template>
    <div class="lonelyResources">
        <ul class="sbResourceList card-columns">
            <div class="card lonelyResource" v-for="r in resources">
                <img :src="previewImage(r)" class="card-img-top" alt="No Resource Preview available">
                <div class="card-body">
                    <h5 class="card-title">
                        {{r.type}} ({{r.id}})
                    </h5>
                    <h6 class="card-subtitle" v-if="r.original_filename">
                        {{r.original_filename}}
                    </h6>
                    <p class="card-text">{{r.notes}}</p>
                    <router-link :to="{name: 'resource-detail', params:{id: r.id}}" class="btn btn-primary">
                        {{$t('pool.open')}}
                    </router-link>
                </div>
            </div>
        </ul>

        <b-pagination-nav
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
    import bPaginationNav from 'bootstrap-vue/src/components/pagination-nav/pagination-nav';
    import {previewImageFirstPage} from "../../../components/serverRoutes";

    export default {
        name: "ResourceLonely",

        data() {
            return {
                page: 1,
                numPages: 1,
            }
        },


        asyncComputed: {
            resources: {
                get() {
                    return this.$store.dispatch('resources/lonely', {page: this.page})
                        .then(({data, current_page, last_page}) => {
                            this.page     = current_page;
                            this.numPages = last_page;
                            return data;
                        });
                }
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

            previewImage(resource) {
                return previewImageFirstPage(resource);
            }
        },

        components: {
            bPaginationNav
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