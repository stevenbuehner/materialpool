<template>
    <div>
        <div class="row" v-for="(sp, key, index) in searchParams" :key="sp.id">
            <div class="col-lg-11 col-lg-11 col-sm-11">
                <search-input v-model="sp.values" @updated="emitSearchUpdated"></search-input>
            </div>
            <div class="col-lg-1 col-lg-1 col-sm-1">
                <div class="btn-group">
                    <button class="btn btn-default"
                            @click="requestAdditionalSearchInputAfter(key)">+
                    </button>
                    <button class="btn btn-default"
                            v-if="index > 0 || searchParams.length > 1"
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
            },

            selfComputedSearchValues() {

                let result = [];

                for (let i in this.searchParams) {
                    const searchKeys = this.searchParams[i].values.map((key) => {
                        return key.item;
                    });

                    // Nur Zeilen für die Suche verwenden, die nicht leer sind
                    if (searchKeys.length > 0) {
                        result.push(searchKeys);
                    }
                }

                return result;
            }

        },

        methods: {

            requestRemovingSarchInput(idParam) {

                if (this.searchParams.length <= 1) {
                    alert('You have to leave at least one searchInput alive');
                    return false;
                }

                this.$delete(this.searchParams, idParam);
                //delete this.searchParams[idParam];

                this.$emit('requestRemovingSarchInput', idParam);
                this.emitSearchUpdated();

            },

            requestAdditionalSearchInputAfter(idParam) {
                const newSearchP = this.getNewSearchParam();
                this.$set(this.searchParams, newSearchP.id, newSearchP);
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

                this.$emit('searchUpdated', this.selfComputedSearchValues);
            }


        },

        created() {

            // Add one initial Searchbar
            if (Object.keys(this.searchParams).length === 0) {
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