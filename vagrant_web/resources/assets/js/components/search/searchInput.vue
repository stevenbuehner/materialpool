<template>
    <vue-select class="v-select"
                name="searchInput"
                :options="options"
                @search="onSearch"
                language="de-DE"
                label="text"
                multiple
                :placeholder="$t('pool.Insert-search-phrase-here')"
                v-model="lineValues"
                :filterable="false"
                @input="onInput">

        <template slot="no-options">
            {{$t('pool.Insert-search-phrase')}}
        </template>

        <template slot="option" slot-scope="option">
            <div class="d-center">
                <span class="icon" :style="{backgroundImage: 'url('+ option.icon+')'}"></span>
                {{ option.text }}
            </div>
        </template>

        <template slot="selected-option" slot-scope="option">
            <div class="selected d-center">
                <span class="icon" :style="{backgroundImage: 'url('+ option.icon+')'}"></span>
                {{ option.text }}
            </div>
        </template>

    </vue-select>
</template>

<script>
    import vueSelect from 'vue-select';

    export default {

        props: {
            lineValues: {
                type: Array,
                required: true,
            }
        },

        data() {
            return {
                options: [],
                firstAlreadyIgnored: false
            };
        },


        model: {
            prop: 'lineValues',
            event: 'updated'
        },


        watch: {},


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

                vm.$store.dispatch('tagsearch/searchTags', search)
                    .then((data) => {
                        vm.options = data.data;
                        loading(false);
                    });

            }, 250),

            onInput(props) {
                // Wird gefeuert bei jeder Änderung durch den Nutzer oder durch des :value Properties (zweiteres ist nervig)

                if (this.firstAlreadyIgnored === true) {
                    this.$emit('updated', props);
                } else {
                    this.firstAlreadyIgnored = true;
                }
            },


        },

        created() {
        },

        components: {
            vueSelect
        },
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