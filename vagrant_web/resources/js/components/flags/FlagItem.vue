<template>
    <div @click="onFlagClicked" class="sb-flagitem-wrapper">
        <flag-icon :class="[disabled ? 'gray' : color, 'materialFlag']"/>
    </div>
</template>

<script>
    import flagIcon from 'svg-icon/dist/svg/game/originals-black-flag.svg';
    import {flagColors} from './flagOptions.js';

    export default {
        name: "FlagItem",

        props: {
            color: {
                type: String,
                required: false,
                default() {
                    return flagColors[0];
                },
                validator(value) {
                    return flagColors.indexOf(value) !== -1;
                }
            },

            disabled: {
                type: Boolean,
                required: false,
                default: false,
            },


            label: {
                type: String,
                required: false,
                default: '1'
            }
        },


        data() {
            return {
                allFlags: flagColors
            };
        },

        methods: {
            onFlagClicked() {
                this.$emit('flag-selected', this.color);
            }
        },

        components: {
            flagIcon
        }

    }
</script>

<style type="scss">

    @import "../../../sass/theme";

    .sb-flagitem-wrapper {
        cursor: pointer;

        .materialFlag {
            width: 1em;
            height: 1em;
            display: block;

            path:first-child {
                fill: white;
            }

            &.blue {
                path:last-child {
                    fill: $blue;
                }

                &:hover path:last-child {
                    fill: $blue-hover;
                }
            }

            &.indigo {
                path:last-child {
                    fill: $indigo;
                }

                &:hover path:last-child {
                    fill: $indigo-hover;
                }
            }

            &.purple {
                path:last-child {
                    fill: $purple;
                }

                &:hover path:last-child {
                    fill: $purple-hover;
                }
            }

            &.red {
                path:last-child {
                    fill: $red;
                }

                &:hover path:last-child {
                    fill: $red-hover;
                }
            }

            &.orange {
                path:last-child {
                    fill: $orange;
                }

                &:hover path:last-child {
                    fill: $orange-hover;
                }
            }

            &.yellow {
                path:last-child {
                    fill: $yellow;
                }

                &:hover path:last-child {
                    fill: $yellow-hover;
                }
            }

            &.green {
                path:last-child {
                    fill: $green;
                }

                &:hover path:last-child {
                    fill: $green-hover;
                }
            }

            &.cyan {
                path:last-child {
                    fill: $cyan;
                }

                &:hover path:last-child {
                    fill: $cyan-hover;
                }
            }

            &.gray {
                path:last-child {
                    fill: $gray-400;
                }

                &:hover path:last-child {
                    fill: $gray-500;
                }
            }

            &.black {
                path:last-child {
                    fill: $black;
                }

                &:hover path:last-child {
                    fill: $black;
                }
            }
        }
    }

</style>