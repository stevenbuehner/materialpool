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
            <hollow-dots-spinner v-if="stillLoading"
                                 :dot-size="10"
                                 :dots-num="3"
                                 :animation-duration="1500"
                                 color="grey"></hollow-dots-spinner>
            <span v-if="stillLoading">Vorschläge werden gesucht ...</span>

            <h4 v-if="!stillLoading && suggestedKeywords.length > 0" class="suggestions">Gefundene Vorschläge:</h4>
            <button type="button"
                    class="btn btn-outline-secondary btn-sm mr-1 mb-1"
                    v-if="!stillLoading"
                    v-for="kw in displayableSuggestedKeywords"
                    :key="'s' + kw.id"
                    @click="requestAddKeyword(kw)"
            >{{kw.title}}
            </button>
        </div>

    </div>
</template>

<script>
    import {createKeywordRoute, searchGuessKeywords} from "./../serverRoutes";
    import axios from 'axios';
    import keyword from './../keyword/keyword.vue';
    import {HollowDotsSpinner} from 'epic-spinners'


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

                if (newValue.length > 1) {
                    this.setStillLoading(true);
                    this.search(this.setStillLoading, newValue, this);
                } else {
                    this.suggestedKeywords = [];
                }

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

                const promise = this.$store.dispatch('keywords/createAndAssign', {
                    title,
                    type: 'key',
                    materialId: this.materialId
                });

            },

            requestAddKeyword(kw) {

                if (this.myKeywords.find((el) => {
                    return el.id === kw.id;
                }) === undefined) {
                    this.$store.dispatch('keywords/updateRelevance', {
                        materialId: this.materialId,
                        keywordId: kw.id
                    }).then((data) => {
                        this.insertOrUpdateKeyword(data);
                    });
                }

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
                    this.$emit('updated', this.myKeywords);
                }
            }


        },

        created() {

            // Deep Copy Keywords
            this.myKeywords = JSON.parse(JSON.stringify(this.keywords));

        },

        components: {
            keyword,
            HollowDotsSpinner
        }
    }
</script>


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