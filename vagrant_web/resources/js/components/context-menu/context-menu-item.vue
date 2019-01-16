<template>
    <li class="menuItem" @click="menuItemClicked" :class="{disabled: disabled, 'with-icon' : icon !== ''}">
        <span class="icon" v-if="icon !== ''" :style="{backgroundImage : 'url(' + icon + ')'}"></span>
        <slot></slot>
    </li>
</template>

<script>
    export default {
        name: "context-menu-item",

        props: {
            disabled: {
                required: false,
                type: Boolean,
                default: false
            },

            icon: {
                required: false,
                type: String,
                default: ''
            }
        },

        methods: {
            menuItemClicked(event) {
                this.$parent.$emit('item-clicked', this);
                this.$emit('click', event);
            }
        },

    }
</script>

<style scoped>
    .menuItem {
        border-bottom: 1px solid #E0E0E0;
        margin: 0;
        padding: 0.5em;
        line-height: 1em;
    }

    .with-icon {
        background-repeat: no-repeat;
        background-position: 0.5em 0.3em;
        background-size: 1.5em;
        padding-left: 2em;
    }

    .menuItem:last-child {
        border-bottom: none;
    }

    .menuItem:hover {
        background-color: #1E88E5;
        color: #FAFAFA;
        cursor: pointer;
    }

    .menuItem.disabled {
        cursor: default;
        color: grey;
        background-color: lightgrey;
    }

    .menuItem > a {
        color: black;
    }


</style>