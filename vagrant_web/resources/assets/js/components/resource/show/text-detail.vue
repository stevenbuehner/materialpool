<template>
    <div>
        <my-text-block :text="resource.content"></my-text-block>
    </div>
</template>

<script>

    import myTextBlock from './../../my-text-block.vue';


    export default {
        mixins: [],

        props: {
            resource: {
                required: true,
                type: Object
            }
        },

        components: {
            myTextBlock
        }

    }
</script>

<style scoped>

</style>