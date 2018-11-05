<template>
    <div>
        <div class="row" v-for="(sp, key, index) in searchParams" :key="sp.id">
            <div class="col-lg-11 col-lg-11 col-sm-11">
                <search-input v-model="sp.values" @updated="emitSearchUpdated($event ,sp.id)"></search-input>
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

    import searchInput from '../../../../components/search/searchInput.vue';


    function getNewSearchParam(id, values) {
        return {
            id,
            values: values || []
        }
    }

    export default {

        props: {
            searchObjects: {
                type: Object,
                default() {
                    return {
                        1: getNewSearchParam(1)
                    };
                }
            }
        },

        data() {
            return {};
        },

        computed: {

            searchParams() {

                let searchParams = {};

                if (this.searchObjects.length === 0) {
                    searchParams[1] = getNewSearchParam(1);
                } else {
                    for (let i in this.searchObjects) {
                        searchParams[i] = getNewSearchParam(i, this.searchObjects[i]);
                    }
                }


                return searchParams;
            },

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

                return getNewSearchParam(nextCounter);
            },

            emitSearchUpdated(data, id) {

                let searchLineItems = [];

                Object.keys(this.searchParams).forEach((key) => {
                    searchLineItems.push(
                        this.searchParams[key].values.map((v) => v.item)
                    );
                });

                this.$emit('searchUpdated', searchLineItems);

            }


        },

        created() {

            // Add one initial searchbar
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