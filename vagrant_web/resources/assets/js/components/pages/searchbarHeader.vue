<template>
    <div>
        <div class="row" v-for="(sp, key) in searchParams" :key="sp.id">
            <div class="col-lg-11 col-lg-11 col-sm-11">
                <search-input @change="searchParamChanged(key, $event)"></search-input>
            </div>
            <div class="col-lg-1 col-lg-1 col-sm-1">
                <div class="btn-group">
                    <button class="btn btn-default" @click="requestAdditionalSearchInputAfter(key)">+</button>
                    <button class="btn btn-default" v-if="key > 0 || searchParams.length > 1"
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
                searchParams: [],
                counter: 0
            };
        },

        computed: {
            searchValues() {
                return this.searchParams.map(el => {

                    return el.values.map(ob => {
                        return ob.item;
                    });

                });
            }
        },

        methods: {

            searchParamChanged(idParam, newValue) {
                // this.items.splice(indexOfItem, 1, newValue)
                console.log(idParam, newValue);
                this.searchParams[idParam].values = newValue.slice();
                this.$emit('searchUpdated', this.searchValues);
            },

            requestRemovingSarchInput(idParam) {
                if (this.searchParams.length <= 1) {
                    alert('You have to leave at least one searchInput alive');
                    return false;
                }

                this.searchParams.splice(idParam, 1);
                this.$emit('searchUpdated', this.searchValues);
            },

            requestAdditionalSearchInputAfter(idParam) {
                this.searchParams.splice(idParam + 1, 0, this.getNewSearchParam());
            },

            getNewSearchParam() {
                this.counter += 1;

                return {
                    id: this.counter,
                    values: []
                }
            }

        },

        created() {
            this.requestAdditionalSearchInputAfter(0);
        },


        components: {
            searchInput,
        }
    }
</script>

<style scoped>


</style>