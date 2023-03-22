<template>
  <div class="sbTree">
    <ul>
      <TreeNode v-for="c in displayedTree" :key="c.id" :node="c" @move="onMove"/>
    </ul>
  </div>
</template>

<script>
import TreeNode    from './TreeNode.vue'
import _debounce   from 'lodash/debounce';
import {cloneDeep} from "lodash";

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
    return {
      displayedTree: []
    };
  },

  computed: {

    searchRegexp() {

      if (this.searchPhrase.length > 0) {
        const parts = this.searchPhrase.split(' ').filter(el => el.length > 0);

        return parts.join('|');
      }

      return '';

    },

  },

  watch: {
    searchPhrase: {
      handler: function (newValue) {

        this.page = 1;
        this.updateKeywordTree();

      },
      immediate: true
    },

    tree: {
      handler: function (newValue) {
        this.updateKeywordTree();
      },
      immediate: true
    }

  },

  methods: {

    updateKeywordTree() {

      //  console.log('updateKeywordTree', this.tree.length);

      if (this.searchPhrase.length === 0) {
        this.displayedTree = this.tree;
      } else {
        _debounce((self) => {
          self.displayedTree = self.filter(self.tree);
        }, 100)(this);
      }
    },

    filter: function filter(tree) {
      const copy   = cloneDeep(tree); // JSON.parse(JSON.stringify(tree));
      const regExp = new RegExp(`.*(${this.searchRegexp}).*`, "gi");
      console.log(regExp);

      const filterList = (node) => {
        const lengthy = node.children && node.children instanceof Array && node.children.length > 0;
        if (lengthy) {
          node.children = node.children.filter(filterList);
        }

        return (node.children instanceof Array && node.children.length > 0) || node.title.match(regExp);
      };

      return copy.filter(filterList);
    },

    onMove({sourceId, targetId}) {
      const callback = this.afterMove;
      this.move(parseInt(sourceId), targetId, callback);
    },

    afterMove() {
      this.updateKeywordTree();
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