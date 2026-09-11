<template>
  <div class="kw-wrapper text-nowrap" :class="[size]">
    <div class="btn btn-sm btn-secondary sb-keyword position-relative"
         :class="{'tag-readonly' : !editable, 'tag-editable' : editable, 'tag-searchable' : searchable, highlighted : highlight}"
         @mousedown.left.stop="keydownStartDrag"
         @click.left.stop=""
         @click.right.stop="openRightClickMenu"
         @dblclick.stop="openKeywordEditModal"
         role="button">
      <div class="sb-progress-bar" :class="{isDragging : dragging.ongoing}" :style="styleObject"></div>
      <component :is="iconName" class="icon"/>
      <span class="text">{{ myKeyword.title }}</span>
      <span class="delete" v-if="removeable" @mousedown.left.stop @click.prevent.stop="removeKeyword">x</span>
    </div>

    <keyword-editor
        ref="keywordEditor"
        :id="keyword.id"
        @saved="onKeywordPropertiesChanged"
        @saving="$emit('saving', $event)"
        @savingError="$emit('savingError', $event)"
        @deleted="onDeleted"
    />

    <context-menu ref="menu">
      <context-menu-item v-if="searchable" @click.stop="goToKeywordSearch">
        {{ $t('pool.search-for-xy', {xy: myKeyword.title}) }}
      </context-menu-item>
      <context-menu-item v-if="editable" @click.stop="openKeywordEditModal">bearbeiten</context-menu-item>
    </context-menu>

  </div>
</template>


<script>
import {BFormInput, BFormSelect}                                     from '@/adapters/bootstrap';
import contextMenu                                                    from '../context-menu/context-menu.vue';
import contextMenuItem                                                from "../context-menu/context-menu-item.vue";
import {keywordSearchLink}                                            from '../serverRoutes';
import {ayceIcon, iconName, keyIcon, langIcon, personIcon, placeIcon} from './keywordDefaultIcons';
import {searchArrayObjectsToSearchQuery}                              from "../search/searchHelper";
import {draggingSupport}                                              from "./dragging.mixin";
import {RELEVANCE_USER_MAX}                                           from "../../apps/config";
import {cloneDeep}                                                    from "lodash";
import {defineAsyncComponent}                                        from 'vue';
import {useKeywordsStore}                                            from '../../apps/main/stores/keywords';

export default {

  name: 'Keyword',

  mixins: [
    draggingSupport
  ],

  props: {
    // Passing in only. Later working with myKeyword (data)
    keyword: {
      type: Object,
      required: true
    },

    materialId: {
      type: Number,
      required: false
    },

    searchlink: {
      type: String,
      required: false,
      default: ''
    },
    size: {
      type: String,
      required: false,
      default: 'normal'
    },

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

  data: function () {
    return {
      menuIsOpen: false,
      myKeyword: {},
    };
  },

  computed: {

    searchLink() {
      return keywordSearchLink(this.myKeyword);
    },

    relevance() {
      if (this.dragging.ongoing === true) {
        return this.dragPercentage * RELEVANCE_USER_MAX;
      } else if (this.myKeyword.pivot) {
        return this.myKeyword.pivot.relevance;
      } else {
        return 0;
      }
    },

    styleObject: function () {
      return {
        width: this.relevance / 300 * 100 + '%',
      }
    },

    iconName() {
      return iconName(this.myKeyword);
    },

  },

  created: function () {

    // Needs to be copied. Because any changes in properties are not recognized in computed properties
    this.myKeyword = cloneDeep(this.keyword); // JSON.parse(JSON.stringify(this.keyword));

  },

  methods: {

    onDraggingDone(dragPercentage) {
      this.updateRelevance(dragPercentage * RELEVANCE_USER_MAX);
    },

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

        const promise = useKeywordsStore().updateRelevance({
          materialId: this.materialId,
          keywordId: this.keyword.id,
          relevance: relevance
        });

        promise.then((keyword) => {

          // Nur für den Fall, dass die Komponente irgendwo eingesetzt wird, wo sich das im Hintergrund nicht aktualisiert
          this.myKeyword.pivot = keyword.pivot;

          this.emitSaved(keyword);

        }).catch((response) => {

          // on failure
          this.$emit('savingPivotError', {
            tag: this.keyword,  // "tag" is used for bibleverses and keywords
            msg: this.parseResponseErrors(response.response)
          });
        });
      } else {

        console.info('Relevance can only be changed in the backend when a material-id is given!');

        let pivot            = this.myKeyword.pivot || {};
        pivot.relevance      = relevance;
        this.myKeyword.pivot = pivot;

        this.emitSaved(this.myKeyword);

      }

    },

    removeKeyword() {

      if (this.materialId) {
        useKeywordsStore().deleteAssignment({
          materialId: this.materialId,
          keywordId: this.myKeyword.id
        })
            .then((response) => {
              this.$emit('removed', this.myKeyword);
            });
      } else {
        console.info('Missing MaterialID -> The association is only removed in frontend!');
        this.$emit('removed', this.myKeyword);
      }

    },

    onKeywordPropertiesChanged(newKeyword) {

      for (let i in newKeyword) {
        this.myKeyword[i] = newKeyword[i];
      }

      this.emitSaved(newKeyword);
    },

    onDeleted() {
      this.$emit('removed', this.keyword);
      this.$emit('deleted', this.keyword);
    },

    emitSaved(newKeyword) {
      this.$emit('saved', newKeyword);
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

    openKeywordEditModal() {

      if (this.editable === true) {
        this.$refs.keywordEditor.show();
      }

    },

    goToKeywordSearch() {
      this.$router.push({
        name: 'search',
        params: {
          search: searchArrayObjectsToSearchQuery([[this.keyword]])
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
    // To avoid recursive imports of "keyword" Component
    // see: https://vuejs.org/v2/guide/components-edge-cases.html#Recursive-Components
    KeywordEditor: defineAsyncComponent(() => import("../modals/editors/keywordEditor")),

    ContextMenuItem: contextMenuItem,
    BFormInput,
    BFormSelect,
    contextMenu,
    keyIcon,
    placeIcon,
    personIcon,
    langIcon: langIcon,
    ayceIcon,
  }

}


</script>

<style lang="scss">

@import "../../../sass/theme";

.kw-wrapper {
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

    .sb-keyword {
      font-size: 0.7em;
      padding: .125rem .25rem;
    }
  }


  > .sb-keyword {
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
        stroke: black;
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
