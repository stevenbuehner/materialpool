<template>
    <div>
        <vue-select class="v-select"
                    multiple
                    placeholder="Text eingeben"
                    :options="selectableOptions"
                    v-model="myTags"
                    :push-tags="true"
                    @search="onSearch"
                    language="de-DE"
        >
            <template slot="no-options">
                Gib einen Suchbegriff ein
            </template>

            <template slot="option" slot-scope="option" label="test">
                <div class="d-center">
                    {{option}}
                </div>
            </template>

            <template slot="selected-option" slot-scope="option">
                <div class="selected d-center">
                    <span class="icon" :style="{backgroundImage: 'url('+ option.item.icon+')'}"></span>
                    <span v-if="option.type === 'b'" class="bibleverse">{{ option.item.label }}</span>
                    <span v-if="option.type === 'k'" class="keyword">{{ option.item.title }}</span>
                </div>
            </template>
        </vue-select>
    </div>
</template>

<script>
    import vueSelect from 'vue-select';
    import {searchGuessRoute2} from "./../serverRoutes";
    import axios from 'axios';

    export default {

        props: {
            keywords: {
                type: Array,
                required: false,
                default() {
                    return [];
                }
            },

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

        data() {
            return {
                selectableOptions: [],
                myTags: []
            };
        },

        computed: {},

        methods: {
            onSearch(search, loading) {
                loading(true);
                this.search(loading, search, this);
            },

            // _.debounce is a function provided by lodash to limit how
            // often a particularly expensive operation can be run.
            // To learn
            // more about the _.debounce function (and its cousin
            // _.throttle), visit: https://lodash.com/docs#debounce
            search: _.debounce((loading, search, vm) => {

                let data = {q: search};

                axios.get(searchGuessRoute2, {params: data})
                    .then(({data}) => {
                        vm.parseSearchResult(data.data);
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
                this.selectableOptions = data;
            }
        },

        created() {
            // Deep Copy Keywords
            let keywordsCopy = JSON.parse(JSON.stringify(this.keywords));

            for (let i in keywordsCopy) {
                this.myTags.push({
                    type: 'k',
                    /*
                    query: {
                        id: keywordsCopy[i].id,
                        type: 'k'
                    },
                    */
                    item: keywordsCopy[i]
                })
            }

            // Deep Copy Bibleverses
            let bibleversesCopy = JSON.parse(JSON.stringify(this.bibleverses));

            for (let i in bibleversesCopy) {
                this.myTags.push({
                    type: 'b',
                    /*
                    query: {
                        from: undefined,
                        to: undefined,
                        type: 'b'
                    },
                    */
                    item: bibleversesCopy[i]
                })
            }

        },

        components: {
            vueSelect
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

    img {
        height: auto;
        max-width: 2.5rem;
        margin-right: 1rem;
    }

    .d-center {
        align-items: center;
        display: inline-flex;
    }

    .v-select .dropdown li {
        border-bottom: 1px solid rgba(112, 128, 144, 0.1);
    }

    .v-select .dropdown li:last-child {
        border-bottom: none;
    }

    .v-select .dropdown li a {
        padding: 10px 20px;
        width: 100%;
        font-size: 1.25em;
        color: #3c3c3c;
    }

    .v-select .dropdown-menu .active > a {
        color: green;
    }
</style>