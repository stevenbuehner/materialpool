<template>
    <div class="from-bot-wrapper" @click="toggleRequest">
        <div class="btn btn-sm from-bot" :class="{'btn-secondary' : fromBot, 'btn-success' : !fromBot}"
             :style="{color: theme.colors.color, backgroundColor: fromBot ? theme.colors.background : theme.colors.progressbar.default}">
            <span class="icon"
                  :style="{backgroundImage : fromBot ? 'url(/img/icons/entypo-plus/laptop.svg)' : 'url(/img/icons/entypo-plus/fingerprint.svg)', }"></span>
            <span class="text">{{ label }}</span>
        </div>
    </div>
</template>

<script>

    import {tagging} from './theme';

    export default {
        name: "from-bot",
        props: {
            fromBot: {
                type: Boolean,
                required: true
            }
        },

        computed: {

            theme() {
                return tagging;
            },

            label() {
                return this.fromBot ? 'from bot' : 'by user';
            }
        },

        methods: {
            toggleRequest() {
                this.$emit('toggleRequest');
            }
        }
    }
</script>

<style scoped>
    .from-bot-wrapper {
        float: left;
        position: relative;
        margin-bottom: 0.5rem;
        margin-right: 0.25rem;
    }

    .from-bot {
        border: 0;
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
</style>