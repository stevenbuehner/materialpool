<template>
  <div class="bibleverse-wrapper text-nowrap" :class="[size]">
    <div class="btn btn-sm btn-secondary sb-bibleverse position-relative"
         :class="{'tag-readonly' : !editable, 'tag-editable' : editable, highlighted : highlight}"
         @mousedown.left.stop="keydownStartDrag"
         @click.left.stop=""
         @mouseover.alt="displayBibleversePopover=true"
         @mouseout.alt="displayBibleversePopover=false"
         @click.right.stop="openRightClickMenu"
         role="button">
      <div class="sb-progress-bar" :class="{isDragging : dragging.ongoing}" :style="styleObject"></div>
      <bible-icon class="icon"></bible-icon>
      <span class="text">{{ optimizedLabel }}</span>
      <span class="delete" v-if="removeable" @mousedown.left.stop @click.prevent.stop="removeBibleverse">x</span>
    </div>

    <context-menu ref="menu">
      <context-menu-item v-if="searchable" @click="goToBibleverseSearch">
        {{ $t('pool.search-for-xy', {xy: optimizedLabel}) }}
      </context-menu-item>
      <context-menu-item @click="displayBibleversePopover=true">
        {{ $t('pool.Read-Bibleverse') }}
      </context-menu-item>
    </context-menu>

    <bible-popover v-if="displayBibleversePopover"
                   :bibleverse="bibleverse"
                   :position="$el"
                   @bible-popover-closerequest="displayBibleversePopover=false"/>
  </div>
</template>


<script>
import contextMenu                       from '../context-menu/context-menu.vue';
import contextMenuItem                   from "../context-menu/context-menu-item.vue";
import {bibleIcon}                       from '../keyword/keywordDefaultIcons';
import {searchArrayObjectsToSearchQuery} from "../search/searchHelper";
import {draggingSupport}                 from "../keyword/dragging.mixin";
import {RELEVANCE_USER_MAX}              from "../../apps/config";
import BiblePopover                    from "./../bible-popover/bible-popover.vue";
import {BibleVerse, BibleVerseService} from "../../helper/BibleverseHelper";
import {cloneDeep}                     from 'lodash';

export default {

  mixins: [
    draggingSupport
  ],

  props: {
    bibleverse: {
      type: Object,
      required: true
    },

    materialId: {
      type: Number,
      required: false
    },

    size: {
      type: String,
      required: false,
      default: 'normal'
    },

    // Submenü zum Suchen nach dieser Bibelstelle aktivieren
    searchable: {
      type: Boolean,
      required: false,
      default: true
    },
    editable: {
      type: Boolean,
      required: false,
      default: true
    },

    // x  zum entfernen der Bibelstelle anzeigen
    removeable: {
      type: Boolean,
      required: false,
      default: false
    },

    /* Whether this biblverse should be displayed in a highlighted colour*/
    highlight: {
      type: Boolean,
      required: false,
      default: false
    }
  },


  model: {
    prop: 'bibleverse',
    event: 'saved'
  },


  data: function () {
    return {
      myBibleverse: {},
      displayBibleversePopover: false,
    };
  },

  computed: {

    relevance() {
      if (this.dragging.ongoing === true) {
        return this.dragPercentage * RELEVANCE_USER_MAX;
      } else if (this.myBibleverse.pivot) {
        return this.myBibleverse.pivot.relevance;
      } else {
        return 0;
      }
    },
    styleObject: function () {
      return {
        width: this.relevance / 300 * 100 + '%',
      }
    },

    optimizedLabel() {
      const bv = new BibleVerse(this.bibleverse.from, this.bibleverse.to);
      return BibleVerseService.bibleVerseToString(bv);
    }
  },

  created: function () {

    // Needs to be copied. Because any changes in properties are not recognized in computed properties
    this.myBibleverse =  cloneDeep(this.bibleverse);

    this.$on('dragging-done', (dragPercentage) => {
      this.updateRelevance(dragPercentage * RELEVANCE_USER_MAX);
    });

  },

  methods: {

    keydownStartDrag(event) {
      if (this.editable) {
        event.stopPropagation();
        this.startDrag(event);
      }
    },

    /* used by mixin */
    updateRelevance(relevance) {

      this.$emit('savingPivot', {relevance: relevance});

      if (this.materialId) {
        this.$store.dispatch('bibleverses/updateRelevance', {
          materialId: this.materialId,
          bibleverseId: this.myBibleverse.id,
          relevance: relevance
        }).then((bibleverse) => {
          // Update this bibleverse data directly
          this.myBibleverse.pivot = bibleverse.pivot;
          this.emitSaved(bibleverse);
        }).catch((response) => {
          this.$emit('savingPivotError', {
            tag: this.myBibleverse,  // "tag" is used for bibleverses and keywords
            msg: this.parseResponseErrors(response.response)
          });
        });
      } else {

        console.info('Can not add bibleverse to material at the server because no materialId given', this.myBibleverse);

        let pivot               = this.myBibleverse.pivot || {};
        pivot.relevance         = relevance;
        this.myBibleverse.pivot = pivot;

        this.emitSaved(this.myBibleverse);

      }


    },

    parseResponseErrors(response) {
      let msg = 'Error! ';

      if (response.data && response.data.errors) {
        for (let i in response.data.errors) {
          msg += i + ': ' + response.data.errors[i] + '. ';
        }
      }

      return msg;

    },

    emitSaved(newBibleverse) {
      this.$emit('saved', newBibleverse);
    },

    emitRemoved() {
      this.$emit('removed', this.myBibleverse);
    },

    removeBibleverse() {

      if (this.materialId) {

        this.$store.dispatch('bibleverses/deleteAssignment', {
          materialId: this.materialId,
          bibleverseId: this.myBibleverse.id
        }).then(() => {
          this.emitRemoved();
        }).catch((response) => {
          console.error('Failed to remove bibleverse', this.myBibleverse);
        });

      } else {

        console.info('Can not remove bibleverse from material at the server because no materialId given', this.myBibleverse);
        this.emitRemoved();

      }
    },

    goToBibleverseSearch() {
      this.$router.push({
        name: 'search',
        params: {
          search: searchArrayObjectsToSearchQuery([[this.myBibleverse]])
        }
      });
    },

    openRightClickMenu(event) {
      if (this.searchable || this.editable || this.removeable) {
        this.$refs.menu.openMenu(event)
      }
    },

  },

  components: {
    BiblePopover,
    contextMenu,
    contextMenuItem,
    bibleIcon
  }

}


</script>

<style lang="scss">

@import "../../../sass/theme";

.bibleverse-wrapper {
  position: relative;
  margin-bottom: 0.25rem;
  margin-right: 0.25rem;

  &.mini {
    margin-bottom: .125rem;
    margin-top: .125rem;
    margin-left: 0;
    margin-right: .125em;

    .icon {
      height: 0.7rem;
      width: 0.7rem;
      margin-right: .05rem;
    }

    .sb-bibleverse {
      font-size: 0.7em;
      padding: .125rem .25rem;
    }
  }

  > .sb-bibleverse {
    // border: $tag-background-colour-hover solid 1px;
    border: none;
    background-color: $tag-background-colour;
    cursor: pointer;

    &:hover {
      background-color: $tag-background-colour-hover;

      .sb-progress-bar {
        background-color: $tag-progressbar-colour-hover;
      }
    }

    .sb-progress-bar {
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      border-radius: .2rem;
      background-color: $tag-progressbar-colour;

      &.isDragging {
        background-color: $tag-progressbar-dragging-colour;
      }
    }

    &.highlighted {
      .sb-progress-bar {
        background-color: $cyan;
      }
    }

    .text {
      color: $tag-font-colour;
      position: relative;
      text-shadow: .05em .05em .2em $tag-background-colour-hover;
    }

    .icon {
      position: relative;
      height: 1rem;
      margin-right: 0.1rem;
      top: -.1rem;

      path {
        fill: black;
      }
    }

    .delete {
      position: relative;
      color: whitesmoke;
      padding-left: 0.25em;
      font-weight: bold;
      cursor: pointer;

      &:hover {
        color: black;
      }
    }
  }
}
</style>