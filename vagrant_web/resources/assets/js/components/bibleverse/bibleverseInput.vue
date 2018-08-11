<template>
    <div class="card">

        <div class="card-header">
            <bibleverse v-for="bv in myBibleverses"
                        :key="getBibleverseKey(bv)"
                        :bibleverse="bv"
                        :material-id="materialId"
                        :removeable="true"
                        @removed="bibleverseRemoved"
            ></bibleverse>

            <transition name="fade">
                <button type="button"
                        v-if="!stillLoading"
                        class="btn btn-outline-secondary btn-sm"
                        v-for="bv in displayableSuggestedBibleverses"
                        :key="'s' + getBibleverseKey(bv)"
                        @click="addBibleverseClick(bv)"
                >
                    <span class="icon" :style="{backgroundImage: 'url('+ bv.icon+')'}"></span>
                    {{bv.label}}
                </button>
            </transition>

        </div>

        <div class="card-body">

            <div class="input-group">
                <input class="form-control" type="text" placeholder="Bibelvers hier eingeben"
                       v-model="searchInput">

                <div class="input-group-append" v-if="stillLoading">
                    <button
                            class="btn btn-outline-secondary spinnerBlock"
                            type="button"
                    >
                        <hollow-dots-spinner :dot-size="10"
                                             :dots-num="3"
                                             :animation-duration="1500"
                                             color="grey"></hollow-dots-spinner>
                    </button>

                </div>

            </div>

        </div>

        <div class="card-footer" v-if="displayableSuggestedBibleverses.length > 0">
            <span v-if="stillLoading">Vorschläge werden gesucht ...</span>
            <h4 v-if="displayableSuggestedBibleverses.length > 0" class="suggestions">Bibelversvorschläge</h4>

        </div>

    </div>
</template>

<script>

    import {bibleverseUpdatePivotRoute, createBibleverseRoute, searchGuessBibleverses} from './../serverRoutes'
    import axios from 'axios';
    import bibleverse from './biblevers.vue'
    import {HollowDotsSpinner} from 'epic-spinners'


    export default {

        props: {
            bibleverses: {
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
            prop: 'bibleverses',
            event: 'updated'
        },

        data() {
            return {
                myBibleverses: [],

                searchInput: '',

                stillLoading: false,
                suggestedBibleverses: [],
            }
        },


        computed: {

            displayableSuggestedBibleverses() {
                return this.suggestedBibleverses.filter((el) => {
                    return true;
                });
            }

        },

        watch: {
            searchInput(newValue, oldValue) {
                this.setStillLoading(true);
                this.search(this.setStillLoading, newValue, this);
            }
        },


        methods: {
            getBibleverseKey(bv) {

                let k = bv.label;

                if (bv.pivot !== undefined && bv.pivot.relevance !== undefined) {
                    k = k + 'r' + bv.pivot.relevance;
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

                axios.get(searchGuessBibleverses, {params: data})
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
                this.suggestedBibleverses = data;
            },

            addBibleverseClick(bv) {
                this.searchInput = '';
                this.addBibleverseToMaterial(bv);
            },

            addBibleverseToMaterial(bv) {

                const promise = new Promise(
                    (resolve, reject) => {

                        if (bv.id === undefined) {
                            this.createNewBibleverse(bv.from, bv.to).then(
                                (bibleverse) => {
                                    resolve(this.appendBibleverseToMaterial(bibleverse.id, this.materialId));
                                }
                            )
                        } else {
                            resolve(this.appendBibleverseToMaterial(bv.id, this.materialId));
                        }
                    }
                );


                promise.then((bibleverse) => {
                    this.myBibleverses.push(bibleverse);
                    this.$emit('updated', this.myBibleverses);
                })

            },

            createNewBibleverse(from, to) {

                let params = {
                    from: from,
                    to: to,
                };

                return axios.post(createBibleverseRoute, params)
                    .then(({data}) => {

                        console.log('created Bibleverse');
                        return data;

                    })
                    .catch((response) => {
                        console.error(response);
                    });
            },

            appendBibleverseToMaterial(bvId, materialId) {

                let params = {
                    _method: 'PUT'
                };

                return axios.post(bibleverseUpdatePivotRoute(materialId, bvId), params)
                    .then(({data}) => {

                        console.log('Bibleverse assigned to material');

                        return data;

                    })
                    .catch((response) => {
                        console.error(response);
                    });

            },


            bibleverseRemoved(bv) {
                let foundIndex = this.myBibleverses.findIndex((el) => {
                    return el.id === bv.id;
                });

                if (foundIndex > 0) {
                    this.myBibleverses.splice(foundIndex, 1);
                    this.$emit('updated', this.myBibleverses);
                }
            }

        },

        created() {

            // Deep Copy bibleverses
            this.myBibleverses = JSON.parse(JSON.stringify(this.bibleverses));

        },

        components: {
            bibleverse,
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
        height: 0.9rem;
        background-repeat: no-repeat;
        top: 0.1rem;
        width: 1rem;
        margin-right: 0.1rem;
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