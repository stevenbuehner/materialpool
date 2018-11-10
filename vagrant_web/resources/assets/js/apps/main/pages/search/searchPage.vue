<template>
    <div class="container">

        <searchbar-header
                @searchUpdated="searchInputChanged"
                :searchObjects="searchObjects"
        ></searchbar-header>

        <hr>

        <searchbar-outcome
                :materialIds="materialIds"
                v-if="!isLoading"
        ></searchbar-outcome>

        <div class="d-flex justify-content-between align-items-center">
            <hollow-dots-spinner
                    :dot-size="10"
                    :dots-num="3"
                    :animation-duration="1500"
                    v-if="isLoading"
                    color="grey"
            ></hollow-dots-spinner>
        </div>

        <b-alert variant="danger" :show="hasError">Error: {{errorMessage}}</b-alert>

        <hr>

        <b-pagination-nav
                v-model="paging.current_page"
                :limit="10"
                :number-of-pages="paging.last_page"
                use-router
                :link-gen="linkGeneration"
                align="center">
        </b-pagination-nav>

    </div>
</template>

<script>
    import searchbarHeader from './searchbarHeader.vue';
    import searchbarOutcome from './searchbarOutcome.vue';
    import bPaginationNav from 'bootstrap-vue/src/components/pagination-nav/pagination-nav';
    import bAlert from 'bootstrap-vue/src/components/alert/alert'

    import {HollowDotsSpinner} from 'epic-spinners'
    import {
        searchArrayItemsToSearchQuery,
        searchQueryStringToSearchQueryArray,
        searchQueryToSearchArrayObjects
    } from "../../../../components/search/searchHelper";

    export default {

        props: {
            query: {
                type: String,
                default: ''
            },

            page: {
                required: false,
                default: 1,
                type: Number
            },

            quicksearch: {
                type: String,
                required: false,
                default: ''
            }
        },

        data() {
            return {
                materialIds: [],
                paging: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    next_page_url: null,
                    per_page: 20,
                    prev_page_url: null,
                    to: 3,
                    total: 3,
                },
                isLoading: false,

                searchObjects: {},

                hasError: false,
                errorMessage: '',
            };
        },

        computed: {
            queryAndPage() {
                return this.query + 'p' + this.page;
            }
        },

        watch: {
            queryAndPage: {
                handler() {
                    this.updateMaterialList();
                    searchQueryToSearchArrayObjects(this.query).then((searchObjects) => {
                            this.searchObjects = searchObjects;
                        }
                    );
                },
                immediate: true
            }
        },

        methods: {

            searchInputChanged(searchLineItems) {
                const query = searchArrayItemsToSearchQuery(searchLineItems);

                if (query !== this.query) {
                    this.$router.push({
                        name: 'search',
                        params: {
                            search: query
                        }
                    });
                }

            },

            updateMaterialList() {

                const searchData = searchQueryStringToSearchQueryArray(this.query);
                this.isLoading   = true;
                this.hasError    = false;

                this.$store.dispatch('search/materials', {
                    query: searchData,
                    page: this.page
                }).then(({materials, paging}) => {
                    this.paging      = paging;
                    this.materialIds = materials.map(m => m.id);
                }).catch((message) => {
                    this.hasError     = true;
                    this.errorMessage = message;
                    this.materialIds  = [];
                }).then(() => {
                    this.isLoading = false;
                });

            },

            linkGeneration(pageNum) {
                return {
                    name: 'search',
                    params: {
                        search: this.query,
                    },
                    query: {
                        page: pageNum,
                    }
                }
            }

        },

        components: {
            searchbarHeader,
            searchbarOutcome,
            HollowDotsSpinner,
            bPaginationNav,
            bAlert
        }
    }
</script>

<style scoped>

</style>