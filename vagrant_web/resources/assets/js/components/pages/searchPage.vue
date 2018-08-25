<template>
    <div>
        <searchbar-header @searchUpdated="updateMaterialList"></searchbar-header>
        <hr>
        <searchbar-outcome :materialIds="materialIds"></searchbar-outcome>
        <hr>
        <searchbar-footer :paging="paging"></searchbar-footer>
    </div>
</template>

<script>
    import searchbarHeader from './searchbarHeader.vue';
    import searchbarOutcome from './searchbarOutcome.vue';
    import searchbarFooter from './searchbarFooter.vue';

    export default {
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
                }
            };
        },

        computed: {},

        methods: {
            updateMaterialList: function (searchData, page) {

                const promise = this.$store.dispatch('search/materials', {
                    query: searchData,
                    page: page
                }).then(({materials, paging}) => {
                    this.paging      = paging;
                    this.materialIds = materials.map(m => m.id);
                });

            },

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