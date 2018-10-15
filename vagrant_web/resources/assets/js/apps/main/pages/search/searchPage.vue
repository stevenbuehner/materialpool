<template>
    <div class="container">

        <searchbar-header
                @searchUpdated="updateMaterialList"
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

        <hr>

        <searchbar-footer
                :paging="paging"
        ></searchbar-footer>

    </div>
</template>

<script>
    import searchbarHeader from './searchbarHeader.vue';
    import searchbarOutcome from './searchbarOutcome.vue';
    import searchbarFooter from './searchbarFooter.vue';
    import {HollowDotsSpinner} from 'epic-spinners'

    export default {

        props: {
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
                lastSearchData: [],
                isLoading: false,
            };
        },

        computed: {},

        watch: {
            page() {
                this.updateMaterialList();
            }
        },

        methods: {

            updateMaterialList: function (searchData, page) {

                page       = page || this.page;
                searchData = searchData || this.lastSearchData;

                this.lastSearchData = searchData;
                this.isLoading      = true;

                this.$store.dispatch('search/materials', {
                    query: searchData,
                    page: page
                }).then(({materials, paging}) => {
                    this.paging      = paging;
                    this.materialIds = materials.map(m => m.id);
                }).then(() => {
                    this.isLoading = false;
                });

            },

        },

        components: {
            searchbarHeader,
            searchbarOutcome,
            searchbarFooter,
            HollowDotsSpinner
        }
    }
</script>

<style scoped>

</style>