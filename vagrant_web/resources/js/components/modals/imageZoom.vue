<template>
  <b-modal ref="imageZoomModal"
           centered
           hide-footer
           lazy
           :title="title"
           :hide-header-close="true"
           size="lg"
           @show="$emit('image-zoom:showing')"
           @hide="$emit('image-zoom:hiding')"
  >
    <div class="zoomImageModal">
      <b-img :src="image"
             fluid
             @click="_hideZoom"></b-img>

      <div class="previous"
           @click.prevent="btnPrevious"
           v-show="hasPrevious">
        <div class="circle">
          <back-arrow v-show="!isFirst"/>
          <redo-icon v-show="isFirst && endless"/>
        </div>
      </div>

      <div class="next"
           @click.prevent="btnNext"
           v-show="hasNext >= 0">
        <div class="circle">
          <forward-arrow v-show="!isLast"/>
          <undo-icon v-show="isLast && endless"/>
        </div>
      </div>
    </div>
  </b-modal>
</template>

<script>
import {BImg, BModal} from '@/adapters/bootstrap';
import undoIcon       from 'svg-icon/dist/svg/subway/undo-1.svg'
import redoIcon       from 'svg-icon/dist/svg/subway/redo-1.svg'
import backArrow      from 'svg-icon/dist/svg/typcn/arrow-back.svg'
import forwardArrow   from 'svg-icon/dist/svg/typcn/arrow-forward.svg'

export default {
  name: "imageZoom",

  props: {
    data: {
      type: Array,
      required: true
    },

    endless: {
      type: Boolean,
      required: false,
      default: true
    },

    start: {
      type: Number,
      required: false,
      default: 0
    }
  },

  data() {
    return {
      currentIndex: Math.min(this.start, this.data.length),
    };
  },

  computed: {

    image() {
      if (this.data && this.data[this.currentIndex] && this.data[this.currentIndex].src)
        return this.data[this.currentIndex].src;
      else
        return '';
    },

    title() {
      if (this.data && this.data[this.currentIndex] && this.data[this.currentIndex].title) {
        return this.data[this.currentIndex].title;
      }
      return '';
    },

    hasPrevious() {
      return (this.currentIndex > 0 || this.endless && this.data.length > 1);
    },

    hasNext() {
      return (this.currentIndex < this.data.length || this.endless && this.isFirst);
    },

    isFirst() {
      return this.currentIndex === 0;
    },

    isLast() {
      return this.currentIndex === (this.data.length - 1);
    },

  },

  methods: {

    btnNext() {

      const nextIndex = this.currentIndex < (this.data.length - 1) ? this.currentIndex + 1 : 0;
      this._showZoom(nextIndex);

    },

    btnPrevious() {

      let prevIndex = this.currentIndex;

      if (this.currentIndex > 0) {
        prevIndex = this.currentIndex - 1;
      } else if (this.currentIndex === 0 && this.endless) {
        prevIndex = this.data.length - 1;
      }

      this._showZoom(prevIndex);

    },

    _showZoom(arrayIndex) {

      this.currentIndex = arrayIndex;
      this.$refs.imageZoomModal.show();

    },

    _hideZoom() {

      this.$refs.imageZoomModal.hide();

    },

    show(index) {

      index = index || this.start;
      this._showZoom(index);

    },

    hide() {

      this._hideZoom();

    }

  },

  components: {
    BModal,
    BImg,
    redoIcon, undoIcon, backArrow, forwardArrow
  }
}
</script>

<style lang="scss">

.zoomImageModal {

  .previous, .next {
    position: absolute;
    color: black;
    font-size: 2em;
    height: 100%;
    top: 0;
    cursor: pointer;
    display: flex;
    justify-content: center;
    flex-direction: column;
    padding: 0;
    overflow: hidden;

    svg {
      height: 1em;
      width: 1em;
    }

    .circle {
      width: 2em;
      height: 2em;
      background-color: white;
      text-align: center;
      box-shadow: 0 0 1em .5em rgba(0, 0, 0, 0.1);
    }

    &:hover .circle {
      box-shadow: 0 0 1em .5em rgba(0, 0, 0, 0.2);
    }

  }

  .previous {
    left: 0;

    .circle {
      margin-right: 1em;
      border-top-right-radius: 50%;
      border-bottom-right-radius: 50%;
    }
  }

  .next {
    right: 0;

    .circle {
      margin-left: 1em;
      border-top-left-radius: 50%;
      border-bottom-left-radius: 50%;
    }
  }

  .previous:hover {
    background-image: linear-gradient(to right, rgb(184, 184, 184), rgba(210, 210, 210, 0.05));
  }

  .next:hover {
    background-image: linear-gradient(to left, rgb(184, 184, 184), rgba(210, 210, 210, 0.05));
  }

  .modal-header {
    justify-content: center;
  }

}

</style>