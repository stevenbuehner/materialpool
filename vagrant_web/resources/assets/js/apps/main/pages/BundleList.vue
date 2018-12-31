<template>
    <div class="container-fluid">
        <bundle v-for="b in bundles"
                :uuid="b.uuid"
                :key="b.id"></bundle>
    </div>
</template>

<script>
    import Bundle from "../../../components/bundles/bundle.vue";
    import bCardGroup from 'bootstrap-vue/src/components/card/card-group';


    export default {
        name: "BundleOverview",

        data() {
            return {
                stillLoading: true,
                errorMessage: ''
            };
        },


        asyncComputed: {
            bundles: {
                get() {
                    return this.$store.dispatch('bundles/allBundles');
                },
                default: null,
                /* watch() {
                    this.forceReload
                }*/
            }
        },

        created() {
        },

        components: {
            Bundle,
            bCardGroup

        }


    }
</script>

<style scoped>

</style>