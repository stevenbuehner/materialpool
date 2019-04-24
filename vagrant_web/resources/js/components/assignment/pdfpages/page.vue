<template>
    <div class="pb-4 pt-2 cell"
         v-show="isVisible"
         :class="{selectable : isSelectable,
         selected : isSelected}"
    >

        <div class="pills">
            <b-badge pill
                     :variant="assignedMaterials === 0 ? 'danger' : 'success'"
            >{{assignedMaterials}}
            </b-badge>
        </div>

        <div class="content-container" @click="handleClick">
            <div class="image-container">
                <transition name="fade">
                    <img v-show="imageLoaded" :src="image" @load="imageLoaded = true"/>
                </transition>
                <materialpool-spinner v-if="!imageLoaded"/>
            </div>

            <div class="banderole"></div>
        </div>

        <div class="menue-container d-flex justify-content-around">
            <div :class="isSelected ? 'minus' : 'plus'" @click="addPageSelected"></div>
            <div class="middle">{{label}}</div>
            <div class="zoom" @click="zoomInRequested"></div>
        </div>

    </div>
</template>

<script>

    import bBadge from 'bootstrap-vue/src/components/badge/badge';
    import MaterialpoolSpinner from "../../spinner/materialpool-spinner";

    export default {

        data: function () {
            return {
                isVisible: true,
                imageLoaded: false,
            }
        },

        props: {
            index: {
                required: true,
                type: Number
            },
            image: {
                type: String
            },
            isSelectable: {
                default: true,
                type: Boolean
            },
            isSelected: {
                default: false,
                type: Boolean
            },
            assignedMaterials: {
                default: 0,
                type: Number
            }
        },

        computed: {
            label: function () {
                return this.index;
            }
        },

        methods: {
            handleClick: function (event) {
                if (this.isSelectable === true) {
                    if (event.shiftKey) {
                        this.lastPageSelected();
                    } else if (event.metaKey) {
                        this.addPageSelected();
                    } else if (event.altKey) {
                        this.zoomInRequested();
                    } else {
                        this.firstPageSelected();
                    }
                }
            },

            firstPageSelected: function () {
                // EventHandler.$emit('firstPageSelected', this.index);
                this.$emit('firstPageSelected', this.index)
            },
            lastPageSelected: function () {
                // EventHandler.$emit('lastPageSelected', this.index);
                this.$emit('lastPageSelected', this.index);
            },
            addPageSelected: function () {
                // EventHandler.$emit('addPageSelection', this.index);
                this.$emit('addPageSelection', this.index);
            },

            zoomInRequested: function () {
                this.$emit('zoomInRequest', this.index);
            },

            hidePage: function () {
                this.isVisible = false;
            },
            showPage: function () {
                this.isVisible = true;
            },

            checkSelectionRequest: function () {
                if (this.isSelectable !== true) {
                    console.log("Page is not selectable!");
                }

                return this.isSelectable === true;
            }
        },

        components: {
            MaterialpoolSpinner,
            bBadge,
        }
    }
</script>

<style scoped>

    .cell {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .pills {
        position: absolute;
        z-index: 100;
        left: 0;
        top: 0;
    }

    .content-container {
        border: 1px solid lightgrey;
        border-radius: 0.25em;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }

    .selected .content-container {
        border: 1px solid #709aed;
        border-radius: 7px;
        outline: none;
        box-shadow: 0 0 0.5em #8eaeed;
        background-color: rgba(142, 174, 237, 0.05);
    }

    .banderole {
        height: 1em;
        width: 100%;
        background-color: #709aed;
        box-shadow: 0 0 0.5em #8eaeed;
        transform: rotate(135deg);
        position: absolute;
        bottom: 10%;
        right: -25%;
        display: none;
    }

    .selected .banderole {
        display: inline-block;
    }

    .image-container {
        overflow: hidden;
        position: relative;
    }

    .image-container img {
        max-height: 101%;
        max-width: 105%;
    }

    .menue-container {
        height: 1.75em;
        font-size: 1em;
        padding-top: 0.5em;
    }

    .menue-container div {
        width: 1.25em;
        height: 100%;
    }

    .menue-container .zoom {
        background: url(/img/icons/entypo-plus/resize-full-screen.svg) no-repeat;
        cursor: pointer;
    }

    .menue-container .middle {
        text-align: center;
    }

    .menue-container .plus {
        background: url(/img/icons/entypo-plus/plus.svg) no-repeat;
        cursor: pointer;
    }

    .menue-container .minus {
        background: url(/img/icons/entypo-plus/minus.svg) no-repeat;
        cursor: pointer;
    }

    .fade-enter-active, .fade-leave-active {
        transition: opacity .5s;
    }

    .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */
    {
        opacity: 0;
    }

</style>