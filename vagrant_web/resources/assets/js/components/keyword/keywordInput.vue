<template>
    <div class="card">

        <div class="card-header">
            <keyword v-for="(kw, index) in myKeywords"
                     :key="getKeywordkey(kw)"
                     v-model="myKeywords[index]"
                     :material-id="materialId"
                     :removeable="!disabled"
                     :editable="!disabled"
                     :searchable="!disabled"
                     :dragable="!disabled"
                     @removed="keywordRemoved"
                     @saved="keywordUpdated(kw, index)"
            ></keyword>
        </div>

        <div class="card-body" v-if="!disabled">

            <div class="input-group">
                <input class="form-control"
                       type="text"
                       :placeholder="$t('pool.Insert-keywordtext-here')"
                       v-model="keywordInput">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary"
                            type="button"
                            :class="{'disabled' : keywordInput.length < 3}"
                            v-if="!stillLoading"
                            @click="requestCreateNewKeyword"
                    >{{$t('pool.new')}}
                    </button>

                    <button v-if="stillLoading"
                            class="btn btn-outline-secondary spinnerBlock"
                            type="button">
                        <hollow-dots-spinner :dot-size="10"
                                             :dots-num="3"
                                             :animation-duration="1500"
                                             color="grey"></hollow-dots-spinner>
                    </button>
                </div>
            </div>

        </div>

        <div class="card-footer" v-if="(displayableSuggestedKeywords.length > 0 || stillLoading) && !disabled">

            <span v-if="stillLoading">{{$t('pool.Looking-for-suggestions')}}</span>

            <h4 v-if="!stillLoading && suggestedKeywords.length > 0">
                {{$t('pool.Suggestions')}}:</h4>
            <transition-group name="fade">
                <button type="button"
                        class="btn btn-outline-secondary btn-sm mr-1 mb-1"
                        v-if="!stillLoading"
                        v-for="kw in displayableSuggestedKeywords"
                        :key="'s' + kw.id"
                        @click="requestAddKeyword(kw)"
                >{{kw.title}}
                </button>
            </transition-group>
        </div>

    </div>
</template>

<script>
    import keyword from './../keyword/keyword.vue';
    import {HollowDotsSpinner} from 'epic-spinners'
    import _ from 'lodash';


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
                required: false
            },

            disabled: {
                type: Boolean,
                required: false,
                default: false
            },

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
            },

            keywords: {
                handler: function () {
                    this.init();
                },
                deep: true,
                imediately: true
            },

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

                vm.$store.dispatch('keywords/search', search)
                    .then((keywords) => {
                        console.log(keywords);
                        vm.suggestedKeywords = keywords;
                    })
                    .catch((err) => {
                        console.error(err);
                    })
                    .then(() => {
                        // Always
                        loading(false);
                    });

            }, 250),


            requestCreateNewKeyword() {
                this.createNewKeyword(this.keywordInput);
                this.keywordInput = '';
            },

            createNewKeyword(title) {

                let promise;

                if (this.materialId) {
                    promise = this.$store.dispatch('keywords/createAndAssign', {
                        title,
                        type: 'key',
                        materialId: this.materialId
                    });
                } else {
                    console.info('Missing Material ID: Association is not stored remotely!');
                    promise = this.$store.dispatch('keywords/create', {
                        title,
                        type: 'key',
                    });
                }

                promise.then((keyword) => {
                    this.insertOrUpdateKeyword(keyword);
                })

            },

            requestAddKeyword(kw) {

                if (this.myKeywords.find((el) => {
                    return el.id === kw.id;
                }) === undefined) {

                    if (this.materialId) {
                        this.$store.dispatch('keywords/updateRelevance', {
                            materialId: this.materialId,
                            keywordId: kw.id
                        }).then((data) => {
                            this.insertOrUpdateKeyword(data);
                        });
                    } else {
                        console.info('Missing Material ID: Association is not stored remotely!');
                        this.insertOrUpdateKeyword(kw);
                    }


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

                this.emitUpdate();
            },

            keywordRemoved(kw) {
                let foundIndex = this.myKeywords.findIndex((el) => {
                    return el.id === kw.id;
                });

                if (foundIndex >= 0) {
                    this.myKeywords.splice(foundIndex, 1);
                    this.emitUpdate();
                }
            },

            /** i.e. pivot or something like that */
            keywordUpdated(kw, index) {
                this.emitUpdate();
            },

            emitUpdate() {
                this.$emit('updated', this.myKeywords);
            },

            init() {

                // Deep Copy Keywords
                this.myKeywords = JSON.parse(JSON.stringify(this.keywords));

            }

        },

        created() {

            this.init();

        },

        components: {
            keyword,
            HollowDotsSpinner
        }
    }
</script>


<style scoped>
    .spinnerBlock {
        width: 6em;
    }

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

    .fade-enter-active, .fade-leave-active {
        transition: opacity .5s;
    }

    .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */
    {
        opacity: 0;
    }
</style>