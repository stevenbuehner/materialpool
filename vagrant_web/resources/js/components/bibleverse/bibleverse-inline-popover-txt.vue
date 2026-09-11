<template>
    <span class="sbTextBibleverse"
          @mouseover="showHovered = true"
          @click.stop="showClicked = true"
          @dblclick.stop="showClicked = true"
          :title="showErrorMessage"
    ><!--
    --><slot>{{ text }}</slot><!--
    --><bible-popover
        v-if="showPopover && !showErrorMessage"
        :bibleverse="normalizedBibleverse"
        :position="$el"
        @bible-popover-closerequest="showHovered=false; showClicked=false"
    />
    </span>
</template>

<script>
import BiblePopover from "./../bible-popover/bible-popover";
import BibleVerse   from '../../../../vendor/stevenbuehner/bible-verse-bundle/js/in/BibleVerse.js';
import {useBibleContentsStore} from '../../apps/main/stores/bibleContents';

export default {
  name: "bibleverse-inline-popover-txt",
  props: {
    text: {
      required: true,
      default: 'missing'
    },

    loadContents: {
      type: Boolean,
      required: false,
      default: false
    },
  },


  data() {
    return {
      showHovered: false,
      showClicked: false,
      showErrorMessage: null,
    };
  },

  computed: {

    showPopover() {
      return (this.showHovered || this.showClicked) && this.normalizedBibleverse !== null;
    },

  },

  methods: {},

  asyncComputed: {

    normalizedBibleverse: {
      get() {
        return useBibleContentsStore()
                   .searchAndGet({search: this.text})
                   .then(({bible, bibleverses}) => {

                     if (Array.isArray(bibleverses) && bibleverses.length > 0) {
                       return new BibleVerse(bibleverses[0].from, bibleverses[0].to);
                     }

                     throw ('Error: No Bibleverses found!');

                   })
                   .catch((errorMessage) => {
                     this.showErrorMessage = errorMessage;
                   });
      },
      default: null,
      lazy: true,
      watch() {
      },
    },

  },

  components: {
    BiblePopover,
  }
}
</script>

<style lang="scss">
@import "../../../sass/theme";

.sbTextBibleverse {
  text-decoration: none;
  border-bottom: 1px dotted gray;
  cursor: pointer;
  // margin-right: 0.25em;

  &.contentLoadable:hover, &.popoverOpened {
    border-bottom-style: solid;
    border-bottom-color: $link-color;
    color: $link-color;
  }
}

.verseWrapper {
  .number {
    font-weight: bold;
  }

  &:not(:first-child) {
    padding-left: .75em;
  }
}

</style>
