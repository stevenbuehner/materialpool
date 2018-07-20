<template>
    <div>
        <searchbar-header @searchUpdated="updateMaterialList"></searchbar-header>
        <hr>
        <searchbar-outcome :materials="materials"></searchbar-outcome>
        <hr>
        <searchbar-footer></searchbar-footer>
    </div>
</template>

<script>
    import searchbarHeader from './searchbarHeader.vue';
    import searchbarOutcome from './searchbarOutcome.vue';
    import searchbarFooter from './searchbarFooter.vue';
    import axios from 'axios';

    export default {
        data() {
            return {
                materials: [],
                paging: {
                    current_page: 1,
                    from: 1,
                    last_page: 1,
                    next_page_url: null,
                    per_page: 20,
                    prev_page_url: null,
                    to: 3,
                    total: 3,
                }
            };
        },

        computed: {
            getSearchUrl() {
                return "/pool/search/get";
            }
        },

        methods: {
            updateMaterialList: function (searchData) {
                console.log('updating', searchData);

                var data = {
                    q: searchData,
                    page: 1
                };

                axios.post(this.getSearchUrl, data)
                    .then((response) => {
                        let result = response.data;

                        this.materials = result.data

                        this.paging = {
                            current_page: result.current_page,
                            from: result.from,
                            last_page: result.last_page,
                            next_page_url: result.next_page_url,
                            per_page: result.per_page,
                            prev_page_url: result.prev_page_url,
                            to: result.to,
                            total: result.total,
                        }
                    });
            }
        },

        components: {
            searchbarHeader,
            searchbarOutcome,
            searchbarFooter
        }
    }
</script>

<style scoped>

</style>