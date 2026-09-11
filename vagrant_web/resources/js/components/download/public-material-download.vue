<template>
  <a class="public-material-download"
     :class="{showButton: isLinkGenerated || linkGenerationIsRunning}"
     @click="doAction($event)" target="_blank"
     :href="link">
        <span :title="$t('pool.generate-link')">
            <cloud-icon v-if="!isLinkGenerated" class="icon showNormal"/>
            <cloud-download-icon v-if="!isLinkGenerated" class="icon showHovered"/>
        </span>

    <materialpool-spinner v-if="linkGenerationIsRunning"/>

    <cloud-check-icon v-if="isLinkGenerated && !isLinkCopiedToClipboard" class="icon"/>
    <span v-if="isLinkGenerated && !isLinkCopiedToClipboard" class="text">{{ $t('pool.copy-link') }}</span>

    <cloud-download-icon v-if="isLinkCopiedToClipboard" class="icon"/>
    <span v-if="isLinkCopiedToClipboard" class="text">{{ $t('pool.download') }}</span>
  </a>
</template>

<script>

import cloudIcon               from '@icons/vendor/svg-icon/svg/icomoon/cloud.svg';
import cloudCheckIcon          from '@icons/vendor/svg-icon/svg/icomoon/cloud-check.svg';
import cloudDownloadIcon       from '@icons/vendor/svg-icon/svg/icomoon/cloud-download.svg';
import {copyStringToClipboard} from "../../helper/copyToClipboard";
import MaterialpoolSpinner     from "../spinner/materialpool-spinner";
import {useMaterialsStore}     from '../../apps/main/stores/materials';

export default {
  name: "public-material-download",

  props: {
    materialId: {
      type: Number,
      required: true
    }
  },

  data() {
    return {
      linkGenerationIsRunning: false,
      isLinkCopiedToClipboard: false,
      link: null
    }
  },

  computed: {
    isLinkGenerated() {
      return this.link !== null;
    }
  },

  methods: {

    doAction(e) {
      if (this.isLinkGenerated === false) {
        event.preventDefault();
        if (!this.linkGenerationIsRunning) {
          this.generateDownloadLink();
        }
        // do nothing --> wait
      } else if (this.isLinkCopiedToClipboard === false) {
        event.preventDefault();
        this.copyDownloadLink();
      } else {

        // Reset
        setTimeout(() => {
          this.linkGenerationIsRunning = false;
          this.isLinkCopiedToClipboard = false;
          this.link                    = null;
        }, 1000);

      }
    },

    generateDownloadLink() {
      useMaterialsStore().createDownloadLink(this.materialId)
          .then(({link, until}) => {
            this.link                    = link;
            this.linkGenerationIsRunning = false;
          })
          .catch(() => {
            this.linkGenerationIsRunning = false;
          })
    },

    copyDownloadLink() {
      copyStringToClipboard(window.location.origin + this.link);
      this.isLinkCopiedToClipboard = true;
    },

  },

  components: {
    MaterialpoolSpinner,
    cloudIcon,
    cloudCheckIcon,
    cloudDownloadIcon,
  }


}
</script>

<style lang="scss">
@import "../../../sass/theme";

.public-material-download {
  cursor: pointer;

  &.showButton {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    vertical-align: middle;
    user-select: none;
    background-color: transparent;
    border: 1px solid $secondary;
    padding: 0.25em 0.35em 0 .5em;
    font-size: 1em;
    line-height: 1em;
    border-radius: 0.25rem;
    transition: $btn-transition;
    color: $secondary;

    &:hover {
      color: white;
      background-color: $secondary;
      text-decoration: none;

      .icon {
        fill: white;
      }
    }
  }

  .icon {
    width: 1.5em;
    height: 1.5em;
    fill: $secondary;
    transition: $btn-transition;
    top: -3px;
    position: relative;
  }

  .showNormal {
    display: inline-block;
  }

  .showHovered {
    display: none;
    top: 0;
  }

  &:hover {
    .showNormal {
      display: none;
    }

    .showHovered {
      display: inline-block;
    }
  }

  .text {
    padding-left: .25em;
  }
}
</style>
