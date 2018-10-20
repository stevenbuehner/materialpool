<template>
    <div class="container-fluid">

        <h1>Keyword Detail</h1>

        <keyword-edit
                :id="id"
        ></keyword-edit>

    </div>
</template>

<script>
    import Vue from 'vue';
    import AsyncComputed from 'vue-async-computed';
    import KeywordEdit from "../../../components/keyword/keywordEdit";

    Vue.use(AsyncComputed);

    export default {
        name: "KeywordDetail",
        components: {KeywordEdit},

        props: {
            id: {
                type: Number,
                required: true
            }
        },

        data() {
            return {
                forceReload: 0,
            }
        },

        asyncComputed: {
            keyword: {
                get() {
                    return this.$store.dispatch('keywords/get', this.id);
                },
                default: null,
                /* watch() {
                    this.forceReload
                }*/
            }
        },


    }
</script>

<style scoped>

</style>