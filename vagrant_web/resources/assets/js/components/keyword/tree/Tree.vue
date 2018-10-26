<template>
    <div class="sbTree">
        <ul>
            <TreeNode v-for="c in filteredTreeNodes" :node="c" :key="c.id" @move="onMove"></TreeNode>
        </ul>
    </div>
</template>

<script>
    import TreeNode from './TreeNode.vue'

    export default {
        name: "Tree",

        props: {
            tree: {
                type: Array,
                required: true,
                default() {
                    return [];
                }
            },

            searchPhrase: {
                type: String,
                required: false,
                default: ''
            },

            limit: {
                type: Number,
                required: false,
                default: 100
            },

            page: {
                type: Number,
                required: false,
                default: 1
            },

            move: {
                type: Function,
                required: true
            }
        },

        data() {
        },

        computed: {

            useSearchPhrase() {
                return this.searchPhrase.length > 0;
            },

            filteredTreeNodes() {
                // SearchFilter
                const tree = this.useSearchPhrase ? this.filter(this.tree) : this.tree;

                // Limit + Paging (nur auf Root-Ebene)
                return tree.slice(this.page - 1, this.limit);
            }

        },

        methods: {
            filter: function filter(tree) {
                const copy   = JSON.parse(JSON.stringify(tree));
                const regExp = new RegExp(`.*${ this.searchPhrase }.*`, "gi");

                const filterList = (node) => {
                    const lengthy = node.children && node.children instanceof Array && node.children.length > 0;
                    if (lengthy) {
                        node.children = node.children.filter(filterList);
                    }

                    return lengthy || node.title.match(regExp);
                };

                return copy.filter(filterList);
            },

            onMove(srcId, targetId) {
                this.move(srcId, targetId);
            }


        },

        components: {
            TreeNode
        }
    }
</script>

<style>
    .sbTree ul {
        list-style: none;
        padding-inline-start: 1.5rem;
    }
</style>