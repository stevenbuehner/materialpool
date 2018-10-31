<template>
    <li class="sbTreeNode" :class="{hasChildren, isOpen, isClosed: !isOpen, isDragged, isDragover, isTemp: node.temp}">
        <span class="carret">
            <single-down class="single" v-once/>
            <double-down class="double" v-once/>
        </span>

        <span v-once @click="toggleOpen"
              class="label"
              draggable="true"
              @dragstart="onDragstart"
              @dragenter="onDragenter"
              @dragleave="onDragleave"
              @dragover.prevent="onDragover"
              @drop.stop="onDrop"
              @dragend="onDragend"
        >
            <component :is="iconName" class="keywordIcon"></component>
            {{label}}
        </span>

        <span @click="openKeywordDetail" v-once>
            <edit-icon class="sbTreeEditIcon"/>
        </span>

        <ul v-if="isOpen">
            <TreeNode v-for="c in children" :node="c" :key="c.id" @move="emitMove"/>
        </ul>
    </li>
</template>

<script>
    import editIcon from 'svg-icon/dist/svg/ionic/edit.svg';
    import singleDown from 'svg-icon/dist/trimmed-svg/awesome/angle-down.svg';
    import doubleDown from 'svg-icon/dist/trimmed-svg/awesome/angle-double-down.svg';
    import {ayceIcon, iconName, keyIcon, langIcon, personIcon, placeIcon} from './../keywordDefaultIcons';

    export default {
        name: "TreeNode",

        props: {
            node: {
                type: Object,
                required: true
            },
        },

        data() {
            return {
                isOpen: false,
                isDragged: false,
                isDragover: false
            }
        },

        computed: {
            label() {
                return this.node.title || '';
            },
            icon() {
                return this.node.icon || null;
            },
            children() {
                return Array.isArray(this.node.children) ? this.node.children : [];
            },
            hasChildren() {
                return this.node.children && this.node.children instanceof Array && this.node.children.length > 0;
            },
            iconName() {
                return iconName(this.node);
            },
        },

        methods: {
            toggleOpen() {
                this.isOpen = !this.isOpen && this.hasChildren;
            },

            open() {
                this.isOpen = true; //  && this.hasChildren;
            },

            close() {
                this.isOpen = false;
            },

            openKeywordDetail() {
                this.$router.push({name: 'keyword-detail', params: {id: this.node.id}});
            },

            onDragstart(event) {
                this.isDragged = true;

                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData("text/plain", this.node.id);
                }
            },

            onDragend(event) {
                this.isDragged = false;
            },

            onDragenter(event) {
                this.isDragover = true;
            },

            onDragleave(event) {
                this.isDragover = false;
            },

            onDragover(event) {
                event.dataTransfer.dropEffect = 'move';
            },

            onDrop(event) {

                if (event.dataTransfer) {
                    const srcId = event.dataTransfer.getData("text/plain");
                    console.log('Dropped ' + srcId + ' into ' + this.node.id);
                    this.emitMove({sourceId: srcId, targetId: this.node.id});
                }

                this.isDragover = false;

                return false;
            },

            emitMove({sourceId, targetId}) {
                this.$emit('move', {sourceId, targetId});
            }
        },

        created() {
            if (this.node.isOpen === true && this.hasChildren) {
                this.isOpen = true;
            }
        },

        components: {
            editIcon,
            singleDown,
            doubleDown,
            ayceIcon,
            keyIcon,
            langIcon,
            personIcon,
            placeIcon
        }


    }
</script>

<style type="scss">
    @import "resources/assets/sass/theme.scss";

    .sbTreeNode {
        display: block;
        margin-top: .2rem;

        &.isTemp .label {
            background-color: $warning;
        }

        .label {
            border: .05rem solid grey;
            padding: .1rem .5rem .2rem .5rem;
            border-radius: .25rem;

            &:hover {
                background-color: $blue;
                color: white;

                svg path {
                    fill: white;
                }
            }

            .keywordIcon {
                height: 1em;
            }
        }

        &.isDragged > .label {
            border: .05rem dotted grey;
            background-color: $blue-hover;

        }

        &.isDragover {
            box-shadow: 0 0 5px #2ecc3b;
            background-color: rgba(102, 204, 120, 0.15);
            padding: 0 5px;
        }

        &.hasChildren {
            .label {
                cursor: pointer;
            }

            .carret > .single {
                display: none;
            }
        }

        &:not(.hasChildren) {
            .carret > .double {
                display: none;
            }
        }

        .sbTreeEditIcon {
            height: .8rem;
            margin-left: 1rem;
            cursor: pointer;
        }

        .carret > svg {
            height: .8rem;
            width: .8rem;
            padding: .1rem 0 .1rem 0;
            transform: rotate(-90deg);
            transition: all ease-in-out .2s;
        }

        &.isOpen > .carret > svg {
            transform: rotate(0deg);
        }

    }


</style>