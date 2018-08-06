<template>
    <div>
        <b-pagination-nav
                v-model="paging.current_page"
                :limit="10"
                :number-of-pages="paging.last_page"
                align="center">

        </b-pagination-nav>
    </div>
</template>

<script>
    import vueSelect from 'vue-select';
    import bPaginationNav from 'bootstrap-vue/src/components/pagination-nav/pagination-nav';

    export default {
        props: {
            paging: {
                type: Object,
                required: true,
                default: {}
            }
        },

        components:
            {
                vueSelect,
                bPaginationNav
            }
    }
</script>

<style scoped>

</style>