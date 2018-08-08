<template>
    <div class="card">

        <div class="card-header">
            <keyword v-for="kw in myKeywords"
                     :key="getKeywordkey(kw)"
                     :keyword="kw"
                     :material-id="materialId"
                     :removeable="true"
                     @removed="keywordRemoved"
            ></keyword>
        </div>

        <div class="card-body">

            <div class="input-group">
                <input class="form-control" type="text" placeholder="Keywordtext hier eingeben"
                       v-model="keywordInput">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary"
                            type="button"
                            :class="{'disabled' : keywordInput.length < 3}"
                            @click="requestCreateNewKeyword"
                    >Neu
                    </button>
                </div>
            </div>

        </div>

        <div class="card-footer" v-if="displayableSuggestedKeywords.length > 0">
            <span v-if="stillLoading">Vorschläge werden gesucht ...</span>
            <h4 v-if="suggestedKeywords.length > 0" class="suggestions">Weitere Vorschläge</h4>
            <button type="button"
                    class="btn btn-outline-secondary btn-sm mr-1 mb-1"
                    v-for="kw in displayableSuggestedKeywords"
                    :key="'s' + kw.id"
                    @click="requestAddKeyword(kw)"
            >{{kw.title}}
            </button>
        </div>

    </div>
</template>

<script>
    import vueSelect from 'vue-select';
    import {createKeywordRoute, materialAddKeywordRoute, searchGuessKeywords} from "./../serverRoutes";
    import axios from 'axios';
    import keyword from './../keyword/keyword.vue';

    export default {

        props: {
            // Only passing in. Later working with myKeywords
            keywords: {
                type: Array,
                required: false,
                default() {
                    return [];
                }
            },

            materialId: {
                type: Number,
                required: true
            }
        },

        model: {
            prop: 'keywords',
            event: 'updated'
        },

        data() {
            return {
                myKeywords: [],

                keywordInput: '',

                stillLoading: false,
                suggestedKeywords: [],

            };
        },

        computed: {

            myKeywordIds() {
                return this.myKeywords.map((el) => {
                    return el.id;
                });
            },

            displayableSuggestedKeywords() {
                return this.suggestedKeywords.filter((el) => {
                    for (let i in this.myKeywordIds) {
                        if (el.id === this.myKeywordIds[i]) {
                            return false;
                        }
                    }

                    return true;
                });
            }

        },

        watch: {
            keywordInput(newValue, oldValue) {
                this.setStillLoading(true);
                this.search(this.setStillLoading, newValue, this);
            }
        },

        methods: {
            getKeywordkey(kw) {

                let k = kw.id;

                if (kw.pivot !== undefined && kw.pivot.relevance !== undefined) {
                    k = k + 'r' + kw.pivot.relevance;
                } else {
                    k = k + 'un';
                }

                return k;
            },

            setStillLoading(isLoading) {
                this.stillLoading = isLoading;
            },


            // _.debounce is a function provided by lodash to limit how
            // often a particularly expensive operation can be run.
            // To learn
            // more about the _.debounce function (and its cousin
            // _.throttle), visit: https://lodash.com/docs#debounce
            search: _.debounce((loading, search, vm) => {

                let data = {q: search};

                axios.get(searchGuessKeywords, {params: data})
                    .then(({data}) => {
                        vm.parseSearchResult(data);
                    })
                    .catch((response) => {
                        console.error(response);
                    })
                    .then(() => {
                        // Always
                        loading(false);
                    });

            }, 250),


            parseSearchResult(data) {
                this.suggestedKeywords = data;
            },

            requestCreateNewKeyword() {
                this.createNewKeyword(this.keywordInput);
                this.keywordInput = '';
            },

            createNewKeyword(title) {

                let params = {
                    title: title
                };

                axios.post(createKeywordRoute, params)
                    .then(({data}) => {

                        console.log('created Keyword');

                        this.requestAddKeyword(data);
                    })
                    .catch((response) => {
                        console.error(response);
                    });
            },

            requestAddKeyword(kw) {

                if (this.myKeywords.find((el) => {
                    return el.id === kw.id;
                }) === undefined) {
                    this.doAddKeyword(kw, this.materialId);
                }

            },

            doAddKeyword(kw, materialId) {

                this.insertOrUpdateKeyword(kw);

                let params = {
                    _method: 'PUT'
                };

                axios.post(materialAddKeywordRoute(materialId, kw.id), params)
                    .then(({data}) => {
                        console.log('added Keyword');
                        this.insertOrUpdateKeyword(data);
                    })
                    .catch((response) => {
                        console.error(response);
                    });

            },

            insertOrUpdateKeyword(kw) {
                let foundIndex = this.myKeywords.findIndex((el) => {
                    return el.id === kw.id;
                });

                if (foundIndex >= 0) {
                    this.myKeywords.splice(foundIndex, 1, kw);
                    // this.$set(this.myKeywords, foundIndex, kw)
                } else {
                    this.myKeywords.push(kw);
                }

                this.$emit('updated', this.myKeywords);
            },

            keywordRemoved(kw) {
                let foundIndex = this.myKeywords.findIndex((el) => {
                    return el.id === kw.id;
                });

                if (foundIndex > 0) {
                    this.myKeywords.splice(foundIndex, 1);
                }
            }


        },

        created() {

            // Deep Copy Keywords
            this.myKeywords = JSON.parse(JSON.stringify(this.keywords));
        },

        components: {
            vueSelect,
            keyword
        }
    }
</script>


<style>
    .selected-tag .close {
        margin-left: 0.25rem;
        top: -.15rem;
        position: relative;
    }
</style>


<style scoped>

    .icon {
        position: relative;
        display: inline-block;
        background-size: contain;
        background-position: 0 0;
        height: 1rem;
        background-repeat: no-repeat;
        width: 1rem;
        margin-right: 0.25rem;
        margin-left: 0;
    }

    .suggestions {
        font-size: 1em;
        font-weight: bold;

    }
</style>