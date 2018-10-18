<template>

    <div class="container-fluid">

        <div class="waitmessage" v-if="treeStillLoading">Keywords are beeing refreshed from the server. Please wait.
        </div>

        <hollow-dots-spinner
                v-if="treeStillLoading"
                :dot-size="10"
                :dots-num="3"
                :animation-duration="1500"
                color="grey"
        ></hollow-dots-spinner>


        <div class="treeWrapper" style="display: inline-block; min-width: 50%" v-if="!treeStillLoading">
            <tree-view
                    :model="treeModel"
                    category="children"
                    :selection="treeSelection"
                    :onSelect="onTreeSelection"
                    :display="display"
                    :dragndrop="dragndrop"
                    :transition="transition"
                    :css="css"
                    :strategies="strategies"
                    :search="search"
                    :labels="{
                        'search.placeholder' : 'Suche ...'
                       }"
                    :openerOpts="{
                        position : 'left'
                    }"
            >
            </tree-view>
        </div>

    </div>

</template>

<script>

    import {TreeView} from '@bosket/vue';
    import {dragndrop} from "@bosket/core"
    import {HollowDotsSpinner} from 'epic-spinners'

    export default {
        name: "KeywordList",

        data() {
            return {
                treeModel: [
                    {
                        title: 'root', draggable: false, children: [
                            {
                                title: 'First', children: []
                            },
                            {
                                title: 'First test', children: [
                                    {title: 'Second', children: []},
                                    {title: 'Third'},
                                ]
                            }
                        ]
                    }
                ],
                treeModelIds: {},
                treeStillLoading: true,


                treeSelection: [],
                dragndrop: {

                    ...dragndrop.selection(
                        () => this.treeModel,
                        m => {
                            // this.$store.dispatch('keywords/update', {})
                            this.treeModel = m
                        }
                    ),

                    drop: (target, event, inputs) => {

                        const src     = this.treeSelection.pop();
                        src.draggable = false;

                        this.$store.dispatch('keywords/update', {
                            id: src.id,
                            data: {
                                parent_id: target.id
                            }
                        }).then((keyword) => {
                            console.log(keyword);
                            this.moveKeyword(keyword, keyword.parent_id);
                        }).catch((response) => {
                            alert('Error while moving keyword!');
                        });

                    },

                    draggable: (_) => {
                        if (_.draggable === false) {
                            return false;
                        } else {
                            return true;
                        }
                    },
                    droppable: true,


                },

                transition: {
                    attrs: {appear: true},
                    props: {name: "TreeViewDemoTransition"}
                },

                css: {TreeView: "TreeViewDemo"},

                strategies: {
                    click: ["toggle-fold"],
                    selection: ["modifiers"],
                    fold: ['opener-control']
                },


            };
        },

        computed: {},

        created() {

            this.$store.dispatch('keywords/getAll')
                .then((allKeywords) => {
                    this.createModelFromKeywords(allKeywords);
                    this.treeStillLoading = false;
                });

        },

        methods: {
            onTreeSelection(newSelection) {
                this.treeSelection = newSelection;
            },

            search: input => item => item.title.match(new RegExp(`.*${ input }.*`, "gi")),

            display: function (item, test) {

                const h = this.$createElement;

                let classes = ['btn', 'btn-sm'];
                if (item.draggable === false) {
                    classes.push('btn-secondary')
                } else {
                    classes.push('btn-outline-secondary')
                }

                let children = [item.title];
                if (item.icon) {
                    children.unshift(h('span', {
                        class: ['keywordlisticon'],
                        style: {backgroundImage: 'url(' + item.icon + ')'}
                    }));
                }

                return h('a', {class: classes, attrs: {href: '#'}}, children);
            },

            createModelFromKeywords(keywords) {

                let ids   = {};
                let model = [];
                let root  = {'title': 'root', draggable: false, children: [], id: null};

                model.push(root);

                for (let i in keywords) {

                    let k      = keywords[i];
                    k.children = [];
                    ids[k.id]  = k;

                    if (k.parent_id) {

                        if (!ids[k.parent_id]) {
                            console.error('Missing Parrent ID for', k)
                        }

                        ids[k.parent_id].children.push(k);

                    } else {
                        root.children.push(k);
                    }
                }

                this.treeModel    = model;
                this.treeModelIds = ids;

            },

            moveKeyword(keyword, targetId) {
                // Detach from tree first
                this.detachKeyword(keyword);
                this.attachKeyword(keyword, targetId);
            },

            detachKeyword(keyword) {
                const oldModel = this.treeModelIds[keyword.id];

                // Remove from Array
                const parent    = (oldModel.parent_id) ? this.treeModelIds[oldModel.parent_id] : this.treeModel[0];
                parent.children = parent.children.filter(kw => kw.id !== keyword.id);
                // parent.children.splice(parent.indexOf(oldModel), 1);

                // Remove from Index-Object
                delete this.treeModelIds[keyword.id];

                console.log(this.treeModel, this.treeModelIds);
            },

            attachKeyword(keyword, targetId) {

                if (!keyword.children || !Array.isArray(keyword.children)) {
                    keyword.children = [];
                }

                if (targetId) {
                    this.treeModelIds[targetId].children.push(keyword);
                } else {
                    const root = this.treeModel[0];
                    root.children.push(keyword);
                }

                this.treeModelIds[keyword.id] = keyword;

            }

        },

        components: {
            TreeView,
            HollowDotsSpinner
        }
    }
</script>

<style>
    /* Search bar */

    .TreeViewDemo > input[type="search"] {
        width: 100%;
        height: 3em;
        transition: border 0.5s;
        border-radius: 0.2rem;
        text-indent: 0.5rem;
        border: 1px solid #6c757d;
        color: #6c757d;
    }

    /* Elements */

    .TreeViewDemo {
        white-space: nowrap;
    }

    .TreeViewDemo ul {
        list-style: none;
        padding-left: 2em;
    }

    .TreeViewDemo li {
        min-width: 100px;
        transition: all 0.25s ease-in-out;
    }

    .TreeViewDemo ul li > .item {
        position: relative;
        margin-left: 1.5em;
    }

    .TreeViewDemo ul li > .item > a {
        vertical-align: middle;
        transition: all 0.25s;
    }

    .TreeViewDemo ul li:not(.disabled) {
        cursor: pointer;
    }

    .TreeViewDemo li {
        margin: .25em 0 0 0;
    }

    /* Root elements */

    .TreeViewDemo ul.depth-0 {
        padding: 20px;
        margin: 0;
        background-color: rgba(255, 255, 255, 0.4);
        user-select: none;
        transition: all 0.25s;
    }

    /* Categories : Nodes with children */

    .TreeViewDemo li.category > .item {
        transition: all 0.25s ease-in-out;
    }

    .TreeViewDemo li.category:not(.folded) > .item {
    }

    /* Category opener */

    .TreeViewDemo .opener {
        display: block;
        vertical-align: middle;
        font-size: 20px;
        cursor: pointer;
        position: absolute;
    }

    .TreeViewDemo .opener::after {
        content: '+';
        display: inline-block;
        transition: all 0.25s;
        font-family: monospace;
    }

    .TreeViewDemo li.category.async > .item > .opener::after {
        content: '!';
    }

    .TreeViewDemo .opener:hover {
        color: #007bff;
    }

    .TreeViewDemo li.category:not(.folded) > .item > .opener::after {
        color: #007bff;
        transform: rotate(45deg);
    }

    @keyframes spin {
        from {
            transform: rotate(0deg)
        }
        to {
            transform: rotate(360deg)
        }
    }

    .TreeViewDemo li.category.loading > .item > .opener::after {
        animation: spin 1s infinite;
    }

    /* Animations on fold / unfold */

    .TreeViewDemoTransition-enter, .TreeViewDemoTransition-leave-to {
        opacity: 0;
        transform: translateX(-50px);
    }

    .TreeViewDemoTransition-enter-active, .TreeViewDemoTransition-leave-active {
        transition: all .3s ease-in-out;
    }

    /* Drag'n'drop */

    .TreeViewDemo li.dragover, .TreeViewDemo ul.dragover {
        box-shadow: 0px 0px 5px #2ecc3b
    }

    .TreeViewDemo ul.dragover {
        background-color: rgba(102, 204, 120, 0.15);
    }

    .TreeViewDemo li.dragover {
        background-color: rgba(102, 204, 120, 0.15);
        padding: 0px 5px;
    }

    .TreeViewDemo li.dragover > span.item {
        border-color: transparent;
    }

    .TreeViewDemo li.nodrop {
        box-shadow: 0px 0px 5px crimson;
        background-color: rgba(255, 20, 60, 0.1);
        padding: 0px 5px;
    }

</style>

<style>
    .keywordlisticon {
        position: relative;
        display: inline-block;
        background-size: contain;
        background-position: 0 0;
        height: 0.9rem;
        background-repeat: no-repeat;
        top: 0.1rem;
        width: 1rem;
        background-image: url(/img/icons/tag.svg);
        margin-right: 0.5rem;
        margin-left: 0;
    }
</style>