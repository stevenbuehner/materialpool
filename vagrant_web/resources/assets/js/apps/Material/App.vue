<template>
    <div class="container-fluid">
        <material-card-listing
                :materials="materials"
        ></material-card-listing>

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
    import MaterialCardListing from "../../components/Material/MaterialCardListing.vue";
    import bPaginationNav from 'bootstrap-vue/src/components/pagination-nav/pagination-nav';

    export default {

        name: 'MaterialApp',

        data() {
            return {
                paging: {
                    current_page: null,
                    last_page: null,
                    per_page: null
                },
                materials: []
            }
        },

        computed: {
            page() {
                return parseInt(this.$route.query.page || 1);
            }
        },

        watch: {
            '$route.query.page': function (newVal, oldVal) {
                this.loadMaterialPage(newVal);
            }
        },

        methods: {
            loadMaterialPage(pageNo) {

                if (this.current_page !== pageNo) {

                    this.materials = [];
                    this.$store.dispatch('materialapp/getMaterialPage', pageNo)
                        .then((data) => {

                            this.materials = data.data;

                            // paging
                            this.paging.current_page = data.current_page;
                            this.paging.last_page    = data.last_page;
                            this.per_page            = data.per_page;

                        });
                }

            },

            linkGeneration(pageNum) {
                return {
                    name: 'material',
                    query: {
                        page: pageNum
                    }
                }
            }
        },

        created() {
            this.loadMaterialPage(this.page);
        },

        components: {
            MaterialCardListing,
            bPaginationNav
        }
    }
</script>

<style scoped>

</style>