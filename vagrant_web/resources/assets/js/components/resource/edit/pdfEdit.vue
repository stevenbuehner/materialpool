<template>
    <div>

    </div>
</template>

<script>
    import resourceLinks from './../resource-links.mixin';

    export default {

        mixins: [resourceLinks],

        props: {
            resource: {
                required: true,
                type: Object
            }
        },

        model: {
            prop: 'resource',
            event: 'update'
        },

        data() {
            return {};
        },

        computed: {},

        methods: {},

        created() {
        },


        components: {}
    }
</script>

<style scoped>

</style>