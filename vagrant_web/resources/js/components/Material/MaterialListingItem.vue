<template>

  <router-link class="card mb-2 d-flex justify-content-start flex-row materialListingItem"
               v-if="material"
               :to="{name: 'material-detail',params: {id: material.id}}">

    <div class="preview" :class="{imageDisplayed: showImage}">

      <img class="image" v-show="showImage" :src="previewImageUrl" @load="showImage = true; imageIsLoading=false;"
           @error="showImage = false; imageIsLoading=false;" :alt="fileTypes">

      <div v-if="material.from_bot" class="is-bot-corner" :title="$t('pool.is-from-bot')">
        <from-bot-icon class="from-bot-icon"/>
      </div>

      <play-icon class="playIcon" v-if="showImage && containsVideoResource"/>

      <span class="text" v-if="!showImage">
                <span>
                    {{ fileTypes }}
                </span>
                <span class="spinner-border" role="status" v-if="imageIsLoading">
                    <span class="sr-only">Loading...</span>
                </span>
            </span>

    </div>

    <div class="card-body" :class="{showMore}">
      <h5 class="card-title">{{ material.title }}</h5>
      <h6 class="card-subtitle mb-2 text-muted">
        <span class="info" v-if="material.author">Von {{ material.author.title }}</span>
        <span class="bundleName" v-if="bundleName">({{ bundleName }})</span>
        <span class="badge badge-pill badge-warning"
              v-if="material.resources.length === 0">{{ $t('pool.no-resources-attached') }}</span>
      </h6>

      <p class="card-text">{{ material.description }}</p>

      <div class="taglist d-flex flex-wrap overflow-hidden" :style="{height: showMore ? 'auto' : '2em'}">
        <keyword v-for="keyword in highlightedKeywords"
                 :key="'k' + keyword.id"
                 :keyword="keyword"
                 :editable="false"
                 :highlight="true"/>
        <biblevers
            v-for="bibleverse in highlightedBibleverses"
            :key="'b' + bibleverse.id"
            :bibleverse="bibleverse"
            :editable="false"
            :highlight="true"/>
        <keyword v-for="keyword in notHighlightedKeywords"
                 :key="'k' + keyword.id"
                 :keyword="keyword"
                 :editable="false"
                 :highlight="false"/>
        <biblevers
            v-for="bibleverse in notHighlightedBibleverses"
            :key="'b' + bibleverse.id"
            :bibleverse="bibleverse"
            :editable="false"
            :highlight="false"/>
      </div>

    </div>

    <div class="more" @click.prevent="showMore = !showMore">
      <span class="arrow" :class="{down:showMore, left: !showMore}"> < </span>
    </div>

  </router-link>
</template>

<script>
import Keyword                  from '../keyword/keyword.vue'
import Biblevers                from "../bibleverse/biblevers.vue";
import materialDetail           from '../../apps/main/pages/MaterialDetail.vue';
import {material_preview_image} from '../serverRoutes';
import materialStoreMixin       from './materialStore.mixin';
import playIcon                 from 'svg-icon/dist/svg/icomoon/play2.svg'
import fromBotIcon              from 'svg-icon/dist/svg/awesome/database.svg'


function sortByRelevance(t1, t2) {
  return t2.pivot.relevance - t1.pivot.relevance;
}

export default {

  mixins: [
    materialStoreMixin
  ],

  props: {
    id: {
      type: Number,
      required: true
    },

    highlightKeywords: {
      type: Array,
      required: false,
      default() {
        return [];
      }
    },

    highlightBibleverses: {
      type: Array,
      required: false,
      default() {
        return [];
      }
    },
  },

  data() {
    return {
      bundleIcon: null,
      showMore: false,
      showImage: false,
      imageIsLoading: true
    }
  },

  computed: {
    highlightedKeywords() {
      return this.material.keywords.filter((k) => this.isKeywordHighlighted(k.id)).sort(sortByRelevance);
    },

    notHighlightedKeywords() {
      return this.material.keywords.filter((k) => !this.isKeywordHighlighted(k.id)).sort(sortByRelevance);
    },

    highlightedBibleverses() {
      return this.material.bibleverses.filter((b) => this.isBibleverseHighlighted(b.from, b.to)).sort(sortByRelevance);
    },

    notHighlightedBibleverses() {
      return this.material.bibleverses.filter((b) => !this.isBibleverseHighlighted(b.from, b.to)).sort(sortByRelevance);
    },

    previewImageUrl() {
      return material_preview_image(this.id);
    },

    fileTypes() {
      const types = {};

      this.material.resources.forEach((resource) => {
        types[resource.type] = types[resource.type] || 0;
        types[resource.type]++;
      });

      return Object.keys(types).map((t) => t.charAt(0).toUpperCase() + t.slice(1)).join(', ');
    },

    containsVideoResource() {
      return this.material.resources.find((r) => {
        return r.type === 'video';
      }) !== undefined;
    },

  },

  asyncComputed: {

    bundleName: {
      get() {
        if (this.material && this.material.icon_of_bundle) {
          return this.$store.dispatch('bundles/getBundleNameById', this.material.icon_of_bundle)
                     .catch(() => {
                       return 'Missing Bundle name. Ups';
                     })
        } else {
          return '';
        }
      },
      default: null,
      watch() {
        this.material;
      }
    },

    material: {
      get() {
        return this.$store.dispatch('materials/getMaterial', this.id);
      },
      default: null,
      watch() {
      }
    }

  },

  watch: {},


  methods: {

    isBibleverseHighlighted(from, to) {
      return this.highlightBibleverses.find((el) => {
        return el.from <= to && el.to >= from;
      }) !== undefined;

    },

    isKeywordHighlighted(keywordId) {
      return !!this.highlightKeywords.find((el) => {
        return el == keywordId;
      });
    },

  },

  components: {
    Biblevers,
    Keyword,
    materialDetail,
    playIcon,
    fromBotIcon
  }
}
</script>

<style lang="scss">

@import "../../../sass/theme";

$preview-font-color: #DEE2E6;
$preview-background-color: #868E96;


.materialListingItem {
  min-height: 8rem;
  color: inherit;
  text-decoration: inherit;

  &:hover {
    color: inherit;
    text-decoration: inherit;
  }

  .preview {
    min-height: 100%;
    position: relative;
    display: flex;
    flex: 5;
    justify-content: center;
    border-bottom-left-radius: $border-radius;
    border-top-left-radius: $border-radius;
    align-items: center;
    overflow: hidden;
    cursor: pointer;

    .is-bot-corner {
      position: absolute;
      width: 3em;
      height: 3em;
      top: 0;
      left: 0;
      background: linear-gradient(to bottom right, rgba(74, 82, 90, 0.85) 50%, #0000 50%) no-repeat;

      .from-bot-icon {
        height: 1em;
        width: 1em;
        margin: .25em;

        path {
          fill: white;
        }
      }
    }

    &:not(.imageDisplayed) {
      background-color: $preview-background-color;
    }

    &.imageDisplayed {
      border-right: $card-border-color 1px solid;
    }

    .image {
      position: absolute;
      object-fit: cover;
      width: 100%;
      height: 100%;

      transition: all .6s ease;
      -webkit-filter: brightness(1);
    }

    &:hover .image {
      -webkit-transform: scale(1.3);
      -webkit-filter: brightness(0.5);
    }

    .text {
      height: 100%;
      color: $preview-font-color;
      font-size: 1.25rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;

      .spinner-border {
        font-size: .5em;
        width: 2em;
        height: 2em;
      }
    }

    .playIcon {
      width: 3em;
      height: 3em;
      z-index: 1;
      fill: $preview-font-color;
    }
  }

  .card-body {
    flex: 20;
    cursor: pointer;

    &:not(.showMore) {
      .card-text {
        max-height: 3rem;
        line-height: 1.5rem;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
      }
    }

    .card-text {

    }

    .tags {
      padding-bottom: 0.25em;
    }
  }


  .more {
    align-items: center;
    display: flex;
    justify-content: center;
    min-height: 100%;
    flex: 1;
    min-width: 1.5em;
    max-width: 2em;
    background-color: $preview-background-color;
    color: $preview-font-color;
    cursor: pointer;
    border-bottom-right-radius: $border-radius;
    border-top-right-radius: $border-radius;

    .arrow.down {
      transition: all .5s ease;
      transform: rotate(-90deg);
    }

    .arrow.left {
      transition: all .5s ease;
      transform: rotate(0deg);
    }
  }
}


</style>
