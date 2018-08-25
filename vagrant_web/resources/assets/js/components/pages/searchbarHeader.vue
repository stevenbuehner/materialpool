<template>
    <div>
        <div class="row" v-for="(sp, key, index) in searchParams" :key="sp.id">
            <div class="col-lg-11 col-lg-11 col-sm-11">
                <search-input v-model="sp.values" @updated="searchParamChanged(key, $event)"></search-input>
            </div>
            <div class="col-lg-1 col-lg-1 col-sm-1">
                <div class="btn-group">
                    <button class="btn btn-default" @click="requestAdditionalSearchInputAfter(key)">+</button>
                    <button class="btn btn-default" v-if="index > 0 || searchParams.length > 1"
                            @click="requestRemovingSarchInput(key)">-
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

    import searchInput from './../search/searchInput.vue';

    export default {

        data() {
            return {
                computedSearchValues: []
            };
        },

        computed: {

            searchParams: {
                get() {
                    return this.$store.state.search.selectedSearchValues;
                },
                set(value) {
                    this.$store.commit('search/setSearchValues', value);
                }
            }

        },

        methods: {

            searchParamChanged(idParam, newValues) {


                const input  = this.$store.state.search.selectedSearchValues;
                const values = [];

                for (let key in input) {
                    values.push(input[key].values.map(ob => {
                        return ob.item;
                    }));
                }

                this.computedSearchValues = values;

                // Triggers the saving for vuex
                // this.searchParams = this.searchParams;
                // this.set('searchParams', idParam, {id: idParam, values: newValues});
                // this.$store.commit('search/setSearchValues', this.searchParams);

                this.emitSearchUpdated();

            },

            requestRemovingSarchInput(idParam) {

                if (this.searchParams.length <= 1) {
                    alert('You have to leave at least one searchInput alive');
                    return false;
                }

                delete this.searchParams[idParam];

                this.emitSearchUpdated();

            },

            requestAdditionalSearchInputAfter(idParam) {
                const searchParam                 = this.getNewSearchParam();
                this.searchParams[searchParam.id] = searchParam;
            },

            getNewSearchParam() {
                let nextCounter = 0;

                Object.keys(this.searchParams).forEach((key) => {
                    nextCounter = Math.max(key, nextCounter);
                });

                nextCounter++;

                return {
                    id: nextCounter,
                    values: []
                }
            },

            emitSearchUpdated() {

                this.$emit('searchUpdated', this.computedSearchValues);
            }


        },

        created() {

            // Add one initial Searchbar
            if (Object.keys(this.searchParams).length == 0) {
                this.requestAdditionalSearchInputAfter(0);
            }

        },


        components: {
            searchInput,
        }
    }
</script>

<style scoped>


</style>