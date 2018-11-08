<template>
    <div :class="['sb-toggle', 'tgl-'+type]">
        <input type="checkbox" :class="['tgl-input', 'tgl']" v-model="myValue" :id="id"/>
        <label class="text tgl-btn" :data-tg-off="offLabel" :data-tg-on="onLabel" :for="id">{{label}}</label>
    </div>
</template>

<script>

    export default {
        name: "toggle",

        props: {
            value: {
                type: Boolean,
                required: true
            },

            // Needs to be unique
            id: {
                type: String,
                required: true
            },

            onLabel: {
                type: String,
                default: 'yes'
            },

            offLabel: {
                type: String,
                default: 'no'
            },

            type: {
                type: String,
                default: 'light',
                validator(value) {
                    return ['light', 'ios', 'skewed', 'flat', 'flip'].indexOf(value) !== -1;
                }
            }
        },

        model: {
            prop: 'value',
            event: 'isToggled'
        },

        data() {
            return {
                myValue: this.value
            };
        },

        computed: {
            label() {

                if (['flip', 'skewed'].indexOf(this.type) !== -1) {
                    return this.myValue ? this.onLabel : this.offLabel;
                } else {
                    return '';
                }

            }
        },

        watch: {
            // Value changed by parent
            value(newValue) {
                this.myValue = newValue;
            },

            myValue(newValue) {
                this._emitToggled(newValue);
            }

        },

        methods: {
            _emitToggled() {
                this.$emit('isToggled', this.myValue);
            },
        }
    }
</script>


<style type="scss">
    @import "resources/assets/sass/theme.scss";

    // CSS Templates from: https://codepen.io/mallendeo/pen/eLIiG?editors=1100

    .sb-toggle {
        margin: 0 1em;
        display: inline-block;

        .tgl {
            display: none;

            // add default box-sizing for this scope
            &,
            &:after,
            &:before,
            & *,
            & *:after,
            & *:before,
            & + .tgl-btn {
                box-sizing: border-box;
                &::selection {
                    background: none;
                }
            }

            + .tgl-btn {
                outline: 0;
                display: block;
                height: 2em;
                position: relative;
                cursor: pointer;
                user-select: none;
                margin: 0;
                &:after,
                &:before {
                    position: relative;
                    display: block;
                    content: "";
                    width: 50%;
                    height: 100%;
                }

                &:after {
                    left: 0;
                }

                &:before {
                    display: none;
                }
            }

            &:checked + .tgl-btn:after {
                left: 50%;
            }
        }

        // themes
        &.tgl-light {
            .tgl-btn {
                background: $gray-400;
                border-radius: 2em;
                width: 4em;
                padding: .125em;
                transition: all .4s ease;

                &:after {
                    border-radius: 50%;
                    background: #fff;
                    transition: all .2s ease;
                }
            }

            :checked + .tgl-btn {
                background: $green;
            }

        }

        &.tgl-ios {
            .tgl-btn {
                background: #fbfbfb;
                border-radius: 2em;
                padding: .125em;
                transition: all .4s ease;
                border: 1px solid #e8eae9;
                width: 4em;

                &:after {
                    border-radius: 2em;
                    background: #fbfbfb;
                    transition: left .3s cubic-bezier(
                                    0.175, 0.885, 0.320, 1.275
                    ),
                    padding .3s ease, margin .3s ease;
                    box-shadow: 0 0 0 1px rgba(0, 0, 0, .1),
                    0 .25em 0 rgba(0, 0, 0, .08);
                }

                &:hover:after {
                    will-change: padding;
                }

                &:active {
                    box-shadow: inset 0 0 0 2em #e8eae9;
                    &:after {
                        padding-right: .8em;
                    }
                }
            }

            :checked + .tgl-btn {
                background: $green;
                &:active {
                    box-shadow: none;
                    &:after {
                        margin-left: -.8em;
                    }
                }
            }
        }

        &.tgl-skewed {
            .tgl-btn {
                overflow: hidden;
                transform: skew(-10deg);
                backface-visibility: hidden;
                transition: all .2s ease;
                font-family: sans-serif;
                background: #888;
                display: inline-block;
                width: calc(100% + 2em);
                color: transparent;

                &:after,
                &:before {
                    transform: skew(10deg);
                    display: inline-block;
                    transition: all .2s ease;
                    width: 100%;
                    text-align: center;
                    position: absolute;
                    line-height: 2em;
                    font-weight: bold;
                    color: #fff;
                    text-shadow: 0 1px 0 rgba(0, 0, 0, .4);
                }

                &:after {
                    left: 100%;
                    content: attr(data-tg-on);
                }

                &:before {
                    left: 0;
                    content: attr(data-tg-off);
                }

                &:active {
                    background: #888;
                    &:before {
                        left: -10%;
                    }
                }
            }

            :checked + .tgl-btn {
                background: $green;
                &:before {
                    left: -100%;
                }

                &:after {
                    left: 0;
                }

                &:active:after {
                    left: 10%;
                }
            }
        }

        &.tgl-flat {
            .tgl-btn {
                padding: 2px;
                transition: all .2s ease;
                background: #fff;
                border: .25em solid $gray-400;
                border-radius: 2em;
                width: 4em;

                &:after {
                    transition: all .2s ease;
                    background: $gray-400;
                    content: "";
                    border-radius: 1em;
                }
            }

            :checked + .tgl-btn {
                border: .25em solid $green;
                &:after {
                    left: 50%;
                    background: $green;
                }
            }
        }

        &.tgl-flip {
            .tgl-btn {
                padding: .5em;
                width: calc(100% + 1em);
                transition: all .2s ease;
                font-family: sans-serif;
                perspective: 100px;
                color: transparent;
                &:after,
                &:before {
                    display: inline-block;
                    transition: all .4s ease;
                    width: 100%;
                    text-align: center;
                    position: absolute;
                    line-height: 2em;
                    font-weight: bold;
                    color: #fff;
                    top: 0;
                    left: 0;
                    backface-visibility: hidden;
                    border-radius: 0.25em;
                }

                &:after {
                    content: attr(data-tg-on);
                    background: $green;
                    transform: rotateY(-180deg);
                }

                &:before {
                    background: $red;
                    content: attr(data-tg-off);
                }

                &:active:before {
                    transform: rotateY(-20deg);
                }
            }

            :checked + .tgl-btn {
                &:before {
                    transform: rotateY(180deg);
                }

                &:after {
                    transform: rotateY(0);
                    left: 0;
                    background: $green;
                }

                &:active:after {
                    transform: rotateY(20deg);
                }
            }
        }
    }

</style>