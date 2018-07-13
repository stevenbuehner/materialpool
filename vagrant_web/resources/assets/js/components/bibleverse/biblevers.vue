<template>
    <div class="bibleverse-wrapper">
        <div class="btn btn-sm btn-secondary sb-bibleverse">
            <span class="icon" :style=""></span>
            <span class="text">{{ bibleverse.label }}</span>
        </div>
    </div>
</template>


<script>
    export default {

        props: {
            bibleverse: {
                type: Object,
                required: true
            },
            size: {
                type: String,
                required: false,
                default: 'small'
            },
            editable: {
                type: Boolean,
                required: false,
                default: true
            }
        },


        data: function () {
            return {};
        },

        computed: {},

        created: function () {


        },

        methods: {}

    }


</script>

<style scoped>

    .bibleverse-wrapper {
        float: left;
        position: relative;
        margin-bottom: 0.5rem;
        margin-right: 0.25rem;
    }

    .sb-bibleverse {
        border: 0px;
    }

    .text {
        position: relative;
    }

    .sb-progress-bar {
        position: absolute;
        background: #97eac8;
        left: 0;
        top: 0;
        height: 100%;
        border-radius: .2rem;
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
        background-image: url(/img/icons/tag.svg);
        margin-right: 0.1rem;
        margin-left: 0;
    }

    input {

    }

</style>