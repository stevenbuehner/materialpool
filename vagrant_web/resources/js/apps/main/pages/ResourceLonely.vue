<template>
    <div class="container">
        <div class="sbResourceList card-columns" v-if="resources.length > 0">
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
                    <b-button variant="danger" @click="btnDelete(r)">{{$t('pool.delete')}}</b-button>
                </div>
            </div>
        </div>

        <div class="alert alert-info" v-if="isLoading">
            {{$t('pool.Loading-resource')}}
        </div>
        <div class="alert alert-success" v-else-if="!isLoading && resources.length === 0">
            {{$t('pool.Congratulations-No-lonely-Resources-found')}}
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
    import {BPaginationNav} from 'bootstrap-vue';
    import {BButton} from 'bootstrap-vue'
    import {previewImageFirstPage} from "../../../components/serverRoutes";

    export default {
        name: "ResourceLonely",

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

                    return this.$store.dispatch('resources/lonely', {page: this.page})
                        .then(({data, current_page, last_page, total}) => {
                            this.page      = current_page;
                            this.numPages  = last_page;
                            this.total     = total;
                            this.isLoading = false;
                            return data;
                        }).catch((message) => {
                            alert(message);
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

            previewImage(resource) {
                return previewImageFirstPage(resource);
            },

            btnDelete(resource) {
                if (confirm('Resource sicher löschen?')) {
                    this.$store.dispatch('resources/deleteResource', resource.id)
                        .then(() => {
                            this.refreshResources++;
                        })
                        .catch(() => {
                            alert('Fehler beim löschen. Seite bitte neu laden!');
                        });

                }

            }
        },

        components: {
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